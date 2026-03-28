<section class="space-y-4">
    <div>
        <h1 class="text-2xl font-semibold">Νέο Όχημα</h1>
        <p class="text-sm text-slate-600">Ατύχημα: <?= e((string) $accident['case_number']) ?></p>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
