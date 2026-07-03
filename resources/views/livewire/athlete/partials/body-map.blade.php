{{--
    Body map SVG stilizzata — fronte e retro affiancati.
    Ogni path ha data-muscle="{slug}" + classe intensità da $intensityMap.

    Muscoli profondi/sovrapposti aggregati visivamente:
      soleus          → visivamente su gastrocnemius (path proprio con data-muscle corretto)
      trapezius_lower → visivamente su trapezius_middle (path proprio, più piccolo)
      brachioradialis → visivamente su brachialis (path proprio avambraccio)
      transverse_abdominis → non rappresentabile, nessun path

    Scala intensità (CSS):
      intensity-0 = #2a2a2a (nessun volume)
      intensity-1 = #1a3a5c (sotto MEV, blu scuro)
      intensity-2 = #c9a227 (sotto MAV, giallo — below_mev warning)
      intensity-3 = #27a050 (in MAV, verde — ottimale)
      intensity-4 = #e07820 (tra MAV e MRV, arancio — approaching_mrv)
      intensity-5 = #c0392b (oltre MRV, rosso — over_mrv)

    Scala assoluta no_landmark:
      intensity-1 = 1-2 set, intensity-2 = 3-4, intensity-3 = 5-7, intensity-4 = 8-10, intensity-5 = 11+
--}}
@php
    $im = $intensityMap ?? [];
    $cls = fn(string $slug) => $im[$slug] ?? 'intensity-0';
@endphp

<svg class="body-map-svg" viewBox="0 0 400 520" xmlns="http://www.w3.org/2000/svg"
     role="img" aria-label="Body map muscolare fronte e retro"
     x-data="bodyMapAlpine()"
     @highlight-muscle.window="highlightSlug($event.detail.slug)">

    {{-- ===================================================================
         VISTA FRONTALE (x offset 0)
         =================================================================== --}}

    <text x="95" y="14" class="body-map-label" text-anchor="middle">Fronte</text>

    {{-- Testa --}}
    <ellipse cx="95" cy="32" rx="16" ry="18" class="body-map-outline"/>

    {{-- Collo --}}
    <rect x="88" y="48" width="14" height="10" rx="3" class="body-map-outline"/>

    {{-- Tronco --}}
    <path d="M65 58 Q55 62 52 80 L50 130 Q50 135 55 136 L135 136 Q140 135 140 130 L138 80 Q135 62 125 58 Z" class="body-map-outline"/>

    {{-- Trapezius upper (fronte, visibile come colmo spalle) --}}
    <path data-muscle="trapezius_upper"
          class="body-map-muscle {{ $cls('trapezius_upper') }}"
          d="M88 50 Q80 52 68 60 L72 66 Q82 58 88 56 Z"
          @click="tap('trapezius_upper')" tabindex="0" role="button" aria-label="Trapezio superiore">
    </path>
    <path data-muscle="trapezius_upper"
          class="body-map-muscle {{ $cls('trapezius_upper') }}"
          d="M102 50 Q110 52 122 60 L118 66 Q108 58 102 56 Z"
          @click="tap('trapezius_upper')">
    </path>

    {{-- Deltoid anterior --}}
    <path data-muscle="deltoid_anterior"
          class="body-map-muscle {{ $cls('deltoid_anterior') }}"
          d="M66 62 Q57 66 54 80 L60 82 Q62 68 68 64 Z"
          @click="tap('deltoid_anterior')" tabindex="0" role="button" aria-label="Deltoide anteriore">
    </path>
    <path data-muscle="deltoid_anterior"
          class="body-map-muscle {{ $cls('deltoid_anterior') }}"
          d="M124 62 Q133 66 136 80 L130 82 Q128 68 122 64 Z"
          @click="tap('deltoid_anterior')">
    </path>

    {{-- Deltoid lateral (fronte — fianco spalla) --}}
    <path data-muscle="deltoid_lateral"
          class="body-map-muscle {{ $cls('deltoid_lateral') }}"
          d="M53 72 Q50 78 50 88 L55 88 Q55 78 56 72 Z"
          @click="tap('deltoid_lateral')" tabindex="0" role="button" aria-label="Deltoide laterale">
    </path>
    <path data-muscle="deltoid_lateral"
          class="body-map-muscle {{ $cls('deltoid_lateral') }}"
          d="M137 72 Q140 78 140 88 L135 88 Q135 78 134 72 Z"
          @click="tap('deltoid_lateral')">
    </path>

    {{-- Pectoralis major sternal --}}
    <path data-muscle="pectoralis_major_sternal"
          class="body-map-muscle {{ $cls('pectoralis_major_sternal') }}"
          d="M72 66 Q68 70 66 82 L92 88 L92 68 Z"
          @click="tap('pectoralis_major_sternal')" tabindex="0" role="button" aria-label="Gran pettorale sternale">
    </path>
    <path data-muscle="pectoralis_major_sternal"
          class="body-map-muscle {{ $cls('pectoralis_major_sternal') }}"
          d="M118 66 Q122 70 124 82 L98 88 L98 68 Z"
          @click="tap('pectoralis_major_sternal')">
    </path>

    {{-- Pectoralis major clavicular --}}
    <path data-muscle="pectoralis_major_clavicular"
          class="body-map-muscle {{ $cls('pectoralis_major_clavicular') }}"
          d="M72 62 L92 64 L92 70 L74 68 Z"
          @click="tap('pectoralis_major_clavicular')" tabindex="0" role="button" aria-label="Gran pettorale clavicolare">
    </path>
    <path data-muscle="pectoralis_major_clavicular"
          class="body-map-muscle {{ $cls('pectoralis_major_clavicular') }}"
          d="M118 62 L98 64 L98 70 L116 68 Z"
          @click="tap('pectoralis_major_clavicular')">
    </path>

    {{-- Rectus abdominis --}}
    <path data-muscle="rectus_abdominis"
          class="body-map-muscle {{ $cls('rectus_abdominis') }}"
          d="M82 90 L88 90 L88 132 L82 132 Z M102 90 L108 90 L108 132 L102 132 Z"
          @click="tap('rectus_abdominis')" tabindex="0" role="button" aria-label="Retto addome">
    </path>

    {{-- Obliques --}}
    <path data-muscle="obliques"
          class="body-map-muscle {{ $cls('obliques') }}"
          d="M68 90 L82 90 L82 132 L62 124 L60 104 Z"
          @click="tap('obliques')" tabindex="0" role="button" aria-label="Obliqui">
    </path>
    <path data-muscle="obliques"
          class="body-map-muscle {{ $cls('obliques') }}"
          d="M122 90 L108 90 L108 132 L128 124 L130 104 Z"
          @click="tap('obliques')">
    </path>

    {{-- Biceps brachii --}}
    <path data-muscle="biceps_brachii"
          class="body-map-muscle {{ $cls('biceps_brachii') }}"
          d="M52 88 L44 88 L40 112 L50 112 Z"
          @click="tap('biceps_brachii')" tabindex="0" role="button" aria-label="Bicipite">
    </path>
    <path data-muscle="biceps_brachii"
          class="body-map-muscle {{ $cls('biceps_brachii') }}"
          d="M138 88 L146 88 L150 112 L140 112 Z"
          @click="tap('biceps_brachii')">
    </path>

    {{-- Brachialis (sotto bicipiti — avambraccio prossimale) --}}
    <path data-muscle="brachialis"
          class="body-map-muscle {{ $cls('brachialis') }}"
          d="M50 112 L40 112 L38 122 L48 122 Z"
          @click="tap('brachialis')" tabindex="0" role="button" aria-label="Brachiale">
    </path>
    <path data-muscle="brachialis"
          class="body-map-muscle {{ $cls('brachialis') }}"
          d="M140 112 L150 112 L152 122 L142 122 Z"
          @click="tap('brachialis')">
    </path>

    {{-- Brachioradialis (avambraccio fronte) — visivamente sotto brachialis --}}
    <path data-muscle="brachioradialis"
          class="body-map-muscle {{ $cls('brachioradialis') }}"
          d="M48 122 L38 122 L36 138 L46 138 Z"
          @click="tap('brachioradialis')" tabindex="0" role="button" aria-label="Brachioradiale">
    </path>
    <path data-muscle="brachioradialis"
          class="body-map-muscle {{ $cls('brachioradialis') }}"
          d="M142 122 L152 122 L154 138 L144 138 Z"
          @click="tap('brachioradialis')">
    </path>

    {{-- Forearm flexors --}}
    <path data-muscle="forearm_flexors"
          class="body-map-muscle {{ $cls('forearm_flexors') }}"
          d="M46 138 L36 138 L34 154 L44 154 Z"
          @click="tap('forearm_flexors')" tabindex="0" role="button" aria-label="Flessori avambraccio">
    </path>
    <path data-muscle="forearm_flexors"
          class="body-map-muscle {{ $cls('forearm_flexors') }}"
          d="M144 138 L154 138 L156 154 L146 154 Z"
          @click="tap('forearm_flexors')">
    </path>

    {{-- Quadriceps --}}
    <path data-muscle="quadriceps"
          class="body-map-muscle {{ $cls('quadriceps') }}"
          d="M60 140 L88 140 L90 200 L58 198 Z"
          @click="tap('quadriceps')" tabindex="0" role="button" aria-label="Quadricipite">
    </path>
    <path data-muscle="quadriceps"
          class="body-map-muscle {{ $cls('quadriceps') }}"
          d="M130 140 L102 140 L100 200 L132 198 Z"
          @click="tap('quadriceps')">
    </path>

    {{-- Adductors (interno coscia) --}}
    <path data-muscle="adductors"
          class="body-map-muscle {{ $cls('adductors') }}"
          d="M88 140 L96 142 L96 196 L90 200 Z"
          @click="tap('adductors')" tabindex="0" role="button" aria-label="Adduttori">
    </path>
    <path data-muscle="adductors"
          class="body-map-muscle {{ $cls('adductors') }}"
          d="M102 140 L94 142 L94 196 L100 200 Z"
          @click="tap('adductors')">
    </path>

    {{-- Tibia / shin (non muscolo target, solo outline) --}}
    <path d="M60 200 L88 200 L86 250 L62 250 Z" class="body-map-outline-light"/>
    <path d="M130 200 L102 200 L104 250 L128 250 Z" class="body-map-outline-light"/>

    {{-- Piedi outline --}}
    <ellipse cx="74" cy="254" rx="14" ry="6" class="body-map-outline-light"/>
    <ellipse cx="116" cy="254" rx="14" ry="6" class="body-map-outline-light"/>

    {{-- Bacino outline (fronte) --}}
    <path d="M56 132 Q55 138 58 142 L132 142 Q135 138 134 132 Z" class="body-map-outline-light"/>

    {{-- ===================================================================
         VISTA POSTERIORE (x offset 205)
         =================================================================== --}}

    <text x="305" y="14" class="body-map-label" text-anchor="middle">Retro</text>

    {{-- Testa --}}
    <ellipse cx="305" cy="32" rx="16" ry="18" class="body-map-outline"/>

    {{-- Collo --}}
    <rect x="298" y="48" width="14" height="10" rx="3" class="body-map-outline"/>

    {{-- Tronco --}}
    <path d="M275 58 Q265 62 262 80 L260 130 Q260 135 265 136 L345 136 Q350 135 350 130 L348 80 Q345 62 335 58 Z" class="body-map-outline"/>

    {{-- Trapezius upper (retro — prominente) --}}
    <path data-muscle="trapezius_upper"
          class="body-map-muscle {{ $cls('trapezius_upper') }}"
          d="M298 50 Q285 52 274 62 L280 68 Q290 58 298 56 Z"
          @click="tap('trapezius_upper')">
    </path>
    <path data-muscle="trapezius_upper"
          class="body-map-muscle {{ $cls('trapezius_upper') }}"
          d="M312 50 Q325 52 336 62 L330 68 Q320 58 312 56 Z"
          @click="tap('trapezius_upper')">
    </path>

    {{-- Trapezius middle --}}
    <path data-muscle="trapezius_middle"
          class="body-map-muscle {{ $cls('trapezius_middle') }}"
          d="M280 68 Q270 72 268 86 L295 92 L295 68 Z"
          @click="tap('trapezius_middle')" tabindex="0" role="button" aria-label="Trapezio medio">
    </path>
    <path data-muscle="trapezius_middle"
          class="body-map-muscle {{ $cls('trapezius_middle') }}"
          d="M330 68 Q340 72 342 86 L315 92 L315 68 Z"
          @click="tap('trapezius_middle')">
    </path>

    {{-- Trapezius lower (piccolo, sotto il medio) --}}
    <path data-muscle="trapezius_lower"
          class="body-map-muscle {{ $cls('trapezius_lower') }}"
          d="M280 92 L295 92 L295 104 L282 102 Z"
          @click="tap('trapezius_lower')" tabindex="0" role="button" aria-label="Trapezio inferiore">
    </path>
    <path data-muscle="trapezius_lower"
          class="body-map-muscle {{ $cls('trapezius_lower') }}"
          d="M330 92 L315 92 L315 104 L328 102 Z"
          @click="tap('trapezius_lower')">
    </path>

    {{-- Deltoid posterior --}}
    <path data-muscle="deltoid_posterior"
          class="body-map-muscle {{ $cls('deltoid_posterior') }}"
          d="M274 62 Q264 66 262 80 L268 82 Q270 68 276 64 Z"
          @click="tap('deltoid_posterior')" tabindex="0" role="button" aria-label="Deltoide posteriore">
    </path>
    <path data-muscle="deltoid_posterior"
          class="body-map-muscle {{ $cls('deltoid_posterior') }}"
          d="M336 62 Q346 66 348 80 L342 82 Q340 68 334 64 Z"
          @click="tap('deltoid_posterior')">
    </path>

    {{-- Deltoid lateral (retro — fianco spalla) --}}
    <path data-muscle="deltoid_lateral"
          class="body-map-muscle {{ $cls('deltoid_lateral') }}"
          d="M263 72 Q260 78 260 88 L265 88 Q265 78 266 72 Z"
          @click="tap('deltoid_lateral')">
    </path>
    <path data-muscle="deltoid_lateral"
          class="body-map-muscle {{ $cls('deltoid_lateral') }}"
          d="M347 72 Q350 78 350 88 L345 88 Q345 78 344 72 Z"
          @click="tap('deltoid_lateral')">
    </path>

    {{-- Rhomboids (tra trapezio medio) --}}
    <path data-muscle="rhomboids"
          class="body-map-muscle {{ $cls('rhomboids') }}"
          d="M295 70 L315 70 L315 92 L295 92 Z"
          @click="tap('rhomboids')" tabindex="0" role="button" aria-label="Romboidi">
    </path>

    {{-- Latissimus dorsi --}}
    <path data-muscle="latissimus_dorsi"
          class="body-map-muscle {{ $cls('latissimus_dorsi') }}"
          d="M266 86 Q262 100 263 118 L290 126 L290 94 Z"
          @click="tap('latissimus_dorsi')" tabindex="0" role="button" aria-label="Gran dorsale">
    </path>
    <path data-muscle="latissimus_dorsi"
          class="body-map-muscle {{ $cls('latissimus_dorsi') }}"
          d="M344 86 Q348 100 347 118 L320 126 L320 94 Z"
          @click="tap('latissimus_dorsi')">
    </path>

    {{-- Erector spinae --}}
    <path data-muscle="erector_spinae"
          class="body-map-muscle {{ $cls('erector_spinae') }}"
          d="M290 94 L310 94 L310 132 L290 132 Z"
          @click="tap('erector_spinae')" tabindex="0" role="button" aria-label="Erettori spinali">
    </path>

    {{-- Triceps brachii --}}
    <path data-muscle="triceps_brachii"
          class="body-map-muscle {{ $cls('triceps_brachii') }}"
          d="M258 88 L250 88 L246 112 L256 112 Z"
          @click="tap('triceps_brachii')" tabindex="0" role="button" aria-label="Tricipite">
    </path>
    <path data-muscle="triceps_brachii"
          class="body-map-muscle {{ $cls('triceps_brachii') }}"
          d="M352 88 L360 88 L364 112 L354 112 Z"
          @click="tap('triceps_brachii')">
    </path>

    {{-- Avambraccio retro (brachioradialis visivamente, data-muscle corretto) --}}
    <path data-muscle="brachioradialis"
          class="body-map-muscle {{ $cls('brachioradialis') }}"
          d="M256 112 L246 112 L244 138 L254 138 Z"
          @click="tap('brachioradialis')">
    </path>
    <path data-muscle="brachioradialis"
          class="body-map-muscle {{ $cls('brachioradialis') }}"
          d="M354 112 L364 112 L366 138 L356 138 Z"
          @click="tap('brachioradialis')">
    </path>

    {{-- Gluteus maximus --}}
    <path data-muscle="gluteus_maximus"
          class="body-map-muscle {{ $cls('gluteus_maximus') }}"
          d="M268 136 L295 136 L293 170 L264 166 Z"
          @click="tap('gluteus_maximus')" tabindex="0" role="button" aria-label="Grande gluteo">
    </path>
    <path data-muscle="gluteus_maximus"
          class="body-map-muscle {{ $cls('gluteus_maximus') }}"
          d="M342 136 L315 136 L317 170 L346 166 Z"
          @click="tap('gluteus_maximus')">
    </path>

    {{-- Gluteus medius (fianco sopra gluteo massimo) --}}
    <path data-muscle="gluteus_medius"
          class="body-map-muscle {{ $cls('gluteus_medius') }}"
          d="M263 118 L268 136 L264 166 L260 150 Z"
          @click="tap('gluteus_medius')" tabindex="0" role="button" aria-label="Medio gluteo">
    </path>
    <path data-muscle="gluteus_medius"
          class="body-map-muscle {{ $cls('gluteus_medius') }}"
          d="M347 118 L342 136 L346 166 L350 150 Z"
          @click="tap('gluteus_medius')">
    </path>

    {{-- Hamstrings --}}
    <path data-muscle="hamstrings"
          class="body-map-muscle {{ $cls('hamstrings') }}"
          d="M266 168 L295 170 L293 218 L264 215 Z"
          @click="tap('hamstrings')" tabindex="0" role="button" aria-label="Ischiocrurali">
    </path>
    <path data-muscle="hamstrings"
          class="body-map-muscle {{ $cls('hamstrings') }}"
          d="M344 168 L315 170 L317 218 L346 215 Z"
          @click="tap('hamstrings')">
    </path>

    {{-- Gastrocnemius --}}
    <path data-muscle="gastrocnemius"
          class="body-map-muscle {{ $cls('gastrocnemius') }}"
          d="M266 218 L285 220 L283 252 L262 250 Z"
          @click="tap('gastrocnemius')" tabindex="0" role="button" aria-label="Gastrocnemio">
    </path>
    <path data-muscle="gastrocnemius"
          class="body-map-muscle {{ $cls('gastrocnemius') }}"
          d="M344 218 L325 220 L327 252 L348 250 Z"
          @click="tap('gastrocnemius')">
    </path>

    {{-- Soleus (sotto gastrocnemio — visivamente aggregato ma data-muscle distinto) --}}
    <path data-muscle="soleus"
          class="body-map-muscle {{ $cls('soleus') }}"
          d="M285 220 L295 222 L293 252 L283 252 Z"
          @click="tap('soleus')" tabindex="0" role="button" aria-label="Soleo">
    </path>
    <path data-muscle="soleus"
          class="body-map-muscle {{ $cls('soleus') }}"
          d="M325 220 L315 222 L317 252 L327 252 Z"
          @click="tap('soleus')">
    </path>

    {{-- Piedi outline retro --}}
    <ellipse cx="275" cy="254" rx="14" ry="6" class="body-map-outline-light"/>
    <ellipse cx="335" cy="254" rx="14" ry="6" class="body-map-outline-light"/>

</svg>

<script>
function bodyMapAlpine() {
    return {
        highlightSlug(slug) {
            // Rimuove highlight precedente
            document.querySelectorAll('[data-muscle]').forEach(el => {
                el.classList.remove('body-map-highlighted');
            });
            // Applica highlight a tutti i path dello slug
            document.querySelectorAll('[data-muscle="' + slug + '"]').forEach(el => {
                el.classList.add('body-map-highlighted');
            });
            // Scroll alla barra corrispondente
            const bar = document.getElementById('muscle-bar-' + slug);
            if (bar) {
                bar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                bar.classList.add('muscle-bar-highlighted');
                setTimeout(() => bar.classList.remove('muscle-bar-highlighted'), 1800);
            }
        },
        tap(slug) {
            this.$dispatch('highlight-muscle', { slug });
            this.$dispatch('scroll-to-bar', { slug });
        }
    };
}
</script>
