<section class="space-y-6">
    <h1 class="text-2xl font-semibold text-slate-900">Προσκλήσεις</h1>

    <article class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-lg font-semibold">Νέα Πρόσκληση</h2>
        <form action="<?= e(url('/admin/invitations')) ?>" method="post" class="grid gap-3 md:grid-cols-3">
            <?= csrf_field() ?>
            <div>
                <label class="mb-1 block text-sm">Ηλεκτρονικό ταχυδρομείο</label>
                <input type="email" name="email" value="<?= e((string) old('email')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                <?php if ($err = error_for('email')): ?><p class="mt-1 text-xs text-rose-700"><?= e($err) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="mb-1 block text-sm">Ρόλος</label>
                <select name="role_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                    <option value="">Επιλέξτε ρόλο</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e((string) $role['id']) ?>" <?= ((string) old('role_id') === (string) $role['id']) ? 'selected' : '' ?>><?= e((string) $role['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($err = error_for('role_id')): ?><p class="mt-1 text-xs text-rose-700"><?= e($err) ?></p><?php endif; ?>
            </div>
            <div class="flex items-end">
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white">Δημιουργία Πρόσκλησης</button>
            </div>
        </form>
    </article>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
            <tr>
                <th class="px-4 py-3">Ηλεκτρονικό ταχυδρομείο</th>
                <th class="px-4 py-3">Ρόλος</th>
                <th class="px-4 py-3">Κατάσταση</th>
                <th class="px-4 py-3">Λήξη πρόσκλησης</th>
                <th class="px-4 py-3">Αποδοχή</th>
                <th class="px-4 py-3">Ενέργειες</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if ($invitations === []): ?>
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">Δεν υπάρχουν προσκλήσεις.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($invitations as $row): ?>
                    <?php $isPending = ((string) $row['status'] === 'pending'); ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $row['email']) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $row['role_label']) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e(invitation_status_label((string) $row['status'])) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $row['expires_at']) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) ($row['accepted_at'] ?? '-')) ?></td>
                        <td class="px-4 py-3 text-slate-700">
                            <?php if ($isPending): ?>
                                <form action="<?= e(url('/admin/invitations/' . $row['id'] . '/cancel')) ?>" method="post" onsubmit="return confirm('Θέλετε σίγουρα να ακυρώσετε την πρόσκληση;');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="rounded-md border border-rose-300 px-3 py-1 text-xs text-rose-700 hover:bg-rose-50">Ακύρωση</button>
                                </form>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>