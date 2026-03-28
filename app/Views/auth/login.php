<section class="mx-auto mt-10 max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="mb-2 text-2xl font-semibold text-slate-900">Σύνδεση Χρήστη</h1>
    <p class="mb-6 text-sm text-slate-600">Συνδεθείτε για να διαχειριστείτε εγγραφές τροχαίων ατυχημάτων.</p>

    <form action="<?= e(url('/login')) ?>" method="post" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Ηλεκτρονικό ταχυδρομείο</label>
            <input id="email" name="email" type="email" autocomplete="email" value="<?= e((string) old('email', '')) ?>"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                   placeholder="π.χ. xristis@paradeigma.gr" required>
            <?php if ($error = error_for('email')): ?>
                <p class="mt-1 text-xs text-rose-700"><?= e($error) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Κωδικός πρόσβασης</label>
            <input id="password" name="password" type="password" autocomplete="current-password"
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
                   placeholder="Εισάγετε τον κωδικό σας" required>
            <?php if ($error = error_for('password')): ?>
                <p class="mt-1 text-xs text-rose-700"><?= e($error) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Είσοδος
        </button>
    </form>
</section>

