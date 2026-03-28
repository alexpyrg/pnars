<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Εφαρμογή') . ' | ' . config('app.name', 'Σύστημα Τροχαίων Ατυχημάτων')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<?php $user = auth()->user(); ?>
<header class="border-b border-slate-200 bg-white shadow-sm">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="<?= e(url('/')) ?>" class="text-lg font-semibold text-slate-800">Σύστημα Τροχαίων Ατυχημάτων</a>
        <?php if ($user): ?>
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                <a class="rounded-md px-3 py-2 hover:bg-slate-100" href="<?= e(url('/')) ?>">Πίνακας Ελέγχου</a>
                <a class="rounded-md px-3 py-2 hover:bg-slate-100" href="<?= e(url('/accidents')) ?>">Ατυχήματα</a>
                <?php if (auth()->hasRole('administrator')): ?>
                    <a class="rounded-md px-3 py-2 hover:bg-slate-100" href="<?= e(url('/admin/users')) ?>">Χρήστες</a>
                    <a class="rounded-md px-3 py-2 hover:bg-slate-100" href="<?= e(url('/admin/invitations')) ?>">Προσκλήσεις</a>
                    <a class="rounded-md px-3 py-2 hover:bg-slate-100" href="<?= e(url('/admin/audit-logs')) ?>">Αρχεία Ελέγχου</a>
                <?php endif; ?>
                <span class="rounded-md bg-slate-100 px-3 py-2"><?= e((string) ($user['full_name'] ?? '')) ?></span>
                <form action="<?= e(url('/logout')) ?>" method="post" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="rounded-md bg-slate-800 px-3 py-2 text-white hover:bg-slate-700">Αποσύνδεση</button>
                </form>
            </nav>
        <?php endif; ?>
    </div>
</header>

<main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <?php if ($success = flash_message('success')): ?>
        <div class="mb-4 rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error = flash_message('error')): ?>
        <div class="mb-4 rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-rose-800">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?= $content ?? '' ?>
</main>
<script src="<?= e(url('/assets/js/app.js')) ?>"></script>
</body>
</html>
