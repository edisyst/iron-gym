<?php

namespace App\Http\Controllers\Backoffice;

use App\Exports\MemberCsvExporter;
use App\Exports\SubscriptionCsvExporter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\MemberExportRequest;
use App\Http\Requests\Backoffice\SubscriptionExportRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function subscriptions(SubscriptionExportRequest $request): StreamedResponse
    {
        return (new SubscriptionCsvExporter($request->filter()))->stream();
    }

    public function members(MemberExportRequest $request): StreamedResponse
    {
        return (new MemberCsvExporter($request->search(), $request->certFilter()))->stream();
    }
}
