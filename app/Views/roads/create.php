<section class="space-y-4">
    <div>
        <h1 class="text-2xl font-semibold">Νέος Δρόμος</h1>
        <p class="text-sm text-slate-600">Ατύχημα: <?= e((string) $accident['case_number']) ?> | Θέση δρόμου: <?= e((string) $roadOrder) ?></p>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
