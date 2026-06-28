<?php

namespace App\Livewire\Backoffice\Members;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Component;

class MemberForm extends Component
{
    public ?int $memberId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $date_of_birth = '';

    public string $fiscal_code = '';

    public string $address = '';

    public string $city = '';

    public string $postal_code = '';

    public string $medical_cert_expiry = '';

    public string $notes = '';

    public bool $is_active = true;

    public bool $create_account = false;

    public string $account_password = '';

    public function mount(?Member $member = null): void
    {
        if ($member && $member->exists) {
            $this->memberId = $member->id;
            $this->first_name = $member->first_name;
            $this->last_name = $member->last_name;
            $this->email = $member->email;
            $this->phone = $member->phone ?? '';
            $this->date_of_birth = $member->date_of_birth?->format('Y-m-d') ?? '';
            $this->fiscal_code = $member->fiscal_code ?? '';
            $this->address = $member->address ?? '';
            $this->city = $member->city ?? '';
            $this->postal_code = $member->postal_code ?? '';
            $this->medical_cert_expiry = $member->medical_cert_expiry?->format('Y-m-d') ?? '';
            $this->notes = $member->notes ?? '';
            $this->is_active = $member->is_active;
        }
    }

    /** @return array<string, string> */
    protected function rules(): array
    {
        // Unicità email escludendo il record corrente in modifica
        $uniqueEmail = 'unique:members,email';
        if ($this->memberId) {
            $uniqueEmail .= ",{$this->memberId}";
        }

        return [
            'first_name' => 'required|string|max:64',
            'last_name' => 'required|string|max:64',
            'email' => "required|email|max:255|{$uniqueEmail}",
            'phone' => 'nullable|string|max:32',
            'date_of_birth' => 'nullable|date|before:today',
            'fiscal_code' => 'nullable|string|max:16',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:128',
            'postal_code' => 'nullable|string|max:10',
            'medical_cert_expiry' => 'nullable|date',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'create_account' => 'boolean',
            'account_password' => $this->create_account && ! $this->memberId
                ? 'required|string|min:8'
                : 'nullable',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        // Converte stringa vuota in null per i campi data nullable
        foreach (['date_of_birth', 'medical_cert_expiry'] as $field) {
            if (($data[$field] ?? '') === '') {
                $data[$field] = null;
            }
        }

        if ($this->memberId) {
            abort_unless(auth()->user()?->hasAnyRole(['gestore', 'trainer']), 403);
            Member::findOrFail($this->memberId)->update($data);
            session()->flash('success', 'Tesserato aggiornato con successo.');
        } else {
            unset($data['create_account'], $data['account_password']);
            $memberData = $data;
            $member = Member::create($memberData);

            if ($this->create_account) {
                $user = User::create([
                    'name' => $member->first_name.' '.$member->last_name,
                    'email' => $member->email,
                    'password' => Hash::make($this->account_password),
                ]);
                $user->email_verified_at = now();
                $user->save();
                $user->assignRole('atleta');
                $member->update(['user_id' => $user->id]);
            }

            session()->flash('success', 'Tesserato creato con successo.');
        }

        $this->redirect(route('backoffice.members.index'));
    }

    public function render(): View
    {
        $title = $this->memberId ? 'Modifica tesserato' : 'Nuovo tesserato';

        return view('livewire.backoffice.members.member-form')
            ->layout('layouts.backoffice')
            ->layoutData(['page_title' => $title]);
    }
}
