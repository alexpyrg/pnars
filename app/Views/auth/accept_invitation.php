<section class="mx-auto mt-10 max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="mb-2 text-2xl font-semibold">Αποδοχή Πρόσκλησης</h1>
    <p class="mb-5 text-sm text-slate-600">Πρόσκληση για: <strong><?= e((string) $invitation['email']) ?></strong> | Ρόλος: <strong><?= e((string) $invitation['role_label']) ?></strong></p>

    <form action="<?= e(url('/invitation/accept')) ?>" method="post" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e((string) $token) ?>">

        <div>
            <label class="mb-1 block text-sm font-medium">Ονοματεπώνυμο</label>
            <input name="full_name" value="<?= e((string) old('full_name')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            <?php if ($err = error_for('full_name')): ?><p class="mt-1 text-xs text-rose-700"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Κωδικός πρόσβασης</label>
            <input name="password" type="password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            <?php if ($err = error_for('password')): ?><p class="mt-1 text-xs text-rose-700"><?= e($err) ?></p><?php endif; ?>
        </div>

        <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Ενεργοποίηση Λογαριασμού</button>
    </form>
</section>
