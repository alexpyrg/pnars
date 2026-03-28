<section class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Ατυχήματα</h1>
        <?php if (($currentUser['role_code'] ?? '') === 'registrar' || ($currentUser['role_code'] ?? '') === 'administrator'): ?>
            <a href="<?= e(url('/accidents/create')) ?>" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Νέα Εγγραφή</a>
        <?php endif; ?>
    </div>

    <form id="accident-filters" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4">
        <input type="text" name="case_number" value="<?= e((string) ($filters['case_number'] ?? '')) ?>" placeholder="Αριθμός υπόθεσης" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <select name="status_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Κατάσταση</option>
            <?php foreach ($statusOptions as $opt): ?>
                <option value="<?= e((string) $opt['id']) ?>" <?= ((string) ($filters['status_id'] ?? '') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="severity_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Σοβαρότητα</option>
            <?php foreach ($severityOptions as $opt): ?>
                <option value="<?= e((string) $opt['id']) ?>" <?= ((string) ($filters['severity_id'] ?? '') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="flagged" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Σήμανση</option>
            <option value="1" <?= ((string) ($filters['flagged'] ?? '') === '1') ? 'selected' : '' ?>>Με ανοικτή σήμανση</option>
            <option value="0" <?= ((string) ($filters['flagged'] ?? '') === '0') ? 'selected' : '' ?>>Χωρίς ανοικτή σήμανση</option>
        </select>

        <input type="date" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input type="date" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input type="text" name="plate_number" value="<?= e((string) ($filters['plate_number'] ?? '')) ?>" placeholder="Πινακίδα" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <select name="vehicle_type_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Τύπος οχήματος</option>
            <?php foreach ($vehicleTypeOptions as $opt): ?>
                <option value="<?= e((string) $opt['id']) ?>" <?= ((string) ($filters['vehicle_type_id'] ?? '') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="entry_completed" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Πληρότητα</option>
            <option value="1" <?= ((string) ($filters['entry_completed'] ?? '') === '1') ? 'selected' : '' ?>>Ολοκληρωμένη</option>
            <option value="0" <?= ((string) ($filters['entry_completed'] ?? '') === '0') ? 'selected' : '' ?>>Μη ολοκληρωμένη</option>
        </select>
        <select name="has_coordinates" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Συντεταγμένες</option>
            <option value="1" <?= ((string) ($filters['has_coordinates'] ?? '') === '1') ? 'selected' : '' ?>>Με συντεταγμένες</option>
            <option value="0" <?= ((string) ($filters['has_coordinates'] ?? '') === '0') ? 'selected' : '' ?>>Χωρίς συντεταγμένες</option>
        </select>
        <select name="information_source_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Πηγή πληροφόρησης</option>
            <?php foreach ($sourceOptions as $opt): ?>
                <option value="<?= e((string) $opt['id']) ?>" <?= ((string) ($filters['information_source_id'] ?? '') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="confidence_level_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Βαθμός βεβαιότητας</option>
            <?php foreach ($confidenceOptions as $opt): ?>
                <option value="<?= e((string) $opt['id']) ?>" <?= ((string) ($filters['confidence_level_id'] ?? '') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="Αναζήτηση σε σύνοψη/σχόλια" class="rounded-md border border-slate-300 px-3 py-2 text-sm md:col-span-3">
        <div class="flex items-center gap-2">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Αναζήτηση</button>
            <button type="button" id="clear-accident-filters" class="rounded-md border border-slate-300 px-4 py-2 text-sm">Καθαρισμός</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table id="accidents-table" data-source-url="<?= e(url('/accidents/datatables')) ?>" class="min-w-full text-sm">
            <thead>
            <tr>
                <th>Υπόθεση</th>
                <th>Ημερομηνία</th>
                <th>Κατάσταση</th>
                <th>Σοβαρότητα</th>
                <th>Καταχωρητής</th>
                <th>Πινακίδες</th>
                <th>Ενέργειες</th>
            </tr>
            </thead>
        </table>
    </div>
</section>

<link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
<script>
(() => {
    const tableElement = document.getElementById('accidents-table');
    const filtersForm = document.getElementById('accident-filters');
    const clearButton = document.getElementById('clear-accident-filters');

    if (!tableElement || !filtersForm || typeof DataTable === 'undefined') {
        return;
    }

    const sourceUrl = tableElement.dataset.sourceUrl;
    if (!sourceUrl) {
        return;
    }

    const escapeHtml = (value) => {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const collectFilters = () => {
        const payload = {};
        const formData = new FormData(filtersForm);
        for (const [key, value] of formData.entries()) {
            payload[key] = value;
        }

        return payload;
    };

    const table = new DataTable('#accidents-table', {
        processing: true,
        serverSide: true,
        searching: false,
        pageLength: 20,
        lengthMenu: [10, 20, 50, 100],
        order: [[1, 'desc']],
        ajax: {
            url: sourceUrl,
            type: 'GET',
            data: (d) => Object.assign(d, collectFilters()),
        },
        columns: [
            {
                data: 'case_number',
                render: (value, type, row) => {
                    if (type !== 'display') {
                        return value;
                    }

                    const flagBadge = row.has_open_flag
                        ? '<span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Σήμανση</span>'
                        : '';

                    return `<span class="font-medium text-slate-900">${escapeHtml(value)}</span>${flagBadge}`;
                },
            },
            {
                data: 'accident_datetime',
                render: (value) => `<span class="text-slate-700">${escapeHtml(value)}</span>`,
            },
            {
                data: 'status_label',
                render: (value) => `<span class="text-slate-700">${escapeHtml(value || '-')}</span>`,
            },
            {
                data: 'severity_label',
                render: (value) => `<span class="text-slate-700">${escapeHtml(value || '-')}</span>`,
            },
            {
                data: 'creator_name',
                render: (value) => `<span class="text-slate-700">${escapeHtml(value || '-')}</span>`,
            },
            {
                data: 'plate_numbers',
                orderable: false,
                render: (value) => `<span class="text-slate-700">${escapeHtml(value || '-')}</span>`,
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (_value, type, row) => {
                    if (type !== 'display') {
                        return '';
                    }

                    const showBtn = `<a href="${escapeHtml(row.show_url)}" class="rounded-md border border-slate-300 px-2 py-1 text-xs">Προβολή</a>`;
                    const editBtn = row.can_edit
                        ? `<a href="${escapeHtml(row.edit_url)}" class="ml-2 rounded-md border border-slate-300 px-2 py-1 text-xs">Επεξεργασία</a>`
                        : '';

                    return `${showBtn}${editBtn}`;
                },
            },
        ],
        language: {
            processing: 'Φόρτωση...',
            lengthMenu: 'Εμφάνιση _MENU_ εγγραφών',
            info: 'Εμφάνιση _START_ έως _END_ από _TOTAL_ εγγραφές',
            infoEmpty: 'Δεν υπάρχουν εγγραφές',
            infoFiltered: '(φιλτραρισμένες από _MAX_ συνολικά)',
            zeroRecords: 'Δεν βρέθηκαν ατυχήματα.',
            emptyTable: 'Δεν υπάρχουν εγγραφές ατυχημάτων.',
            paginate: {
                first: 'Πρώτη',
                previous: 'Προηγούμενη',
                next: 'Επόμενη',
                last: 'Τελευταία',
            },
        },
    });

    filtersForm.addEventListener('submit', (event) => {
        event.preventDefault();
        table.page(0).draw(false);
    });

    clearButton?.addEventListener('click', () => {
        filtersForm.reset();
        table.page(0).draw(false);
    });
})();
</script>
