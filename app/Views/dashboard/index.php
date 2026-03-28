<section class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-slate-900">Πίνακας Ελέγχου</h1>
        <p class="mt-2 text-sm text-slate-600">Καλώς ήρθατε, <?= e((string) ($user['full_name'] ?? 'Χρήστης')) ?>.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Ατυχήματα</h2>
            <p class="mt-2 text-sm text-slate-700">Καταχώρηση, αναζήτηση και παρακολούθηση ροής.</p>
            <a href="<?= e(url('/accidents')) ?>" class="mt-3 inline-block rounded-md border border-slate-300 px-3 py-2 text-sm">Μετάβαση</a>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Ρόλος</h2>
            <p class="mt-2 text-sm text-slate-700"><?= e((string) ($user['role_label_el'] ?? '-')) ?></p>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Κατάσταση Συστήματος</h2>
            <p class="mt-2 text-sm text-emerald-700">Ενεργό</p>
        </article>
    </div>
</section>
