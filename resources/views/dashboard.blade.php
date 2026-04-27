<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Blast Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    <main class="dashboard">
        <header class="hero">
            <div>
                <p class="eyebrow">WhatsApp Ops Center</p>
                <h1>Blast & Number Checker</h1>
                <p class="subtitle">UI modern perpaduan biru-putih untuk operasional yang cepat, jelas, dan nyaman.</p>
            </div>
            <div class="hero-stats">
                <article>
                    <p>Total Blast Hari Ini</p>
                    <strong id="stat-total-blast">-</strong>
                </article>
                <article>
                    <p>Berhasil</p>
                    <strong id="stat-success">-</strong>
                </article>
                <article>
                    <p>Gagal</p>
                    <strong id="stat-failed">-</strong>
                </article>
            </div>
        </header>

        <section class="grid">
            <article class="card">
                <h2>Kirim Blast</h2>
                <p class="muted">Form ini langsung memanggil endpoint API dan menampilkan hasilnya tanpa reload halaman.</p>

                <form class="form" id="blast-form" novalidate>
                    <label>
                        Product ID
                        <input id="product_id" name="product_id" type="number" placeholder="contoh: 1" min="1" required>
                    </label>

                    <label>
                        Category
                        <select id="category" name="category" required>
                            <option value="INCIDENT">INCIDENT</option>
                            <option value="MAINTENANCE">MAINTENANCE</option>
                        </select>
                    </label>

                    <label>
                        Requested By
                        <input id="requested_by" name="requested_by" type="email" placeholder="admin@company.com">
                    </label>

                    <label>
                        Payload (JSON)
                        <textarea id="payload_json" rows="6" required placeholder='{"title":"Maintenance Notice","message":"Sistem maintenance jam 21:00"}'></textarea>
                    </label>

                    <div class="form-actions">
                        <button type="button" class="secondary" id="format-json-btn">Format JSON</button>
                        <button type="submit" id="submit-btn">Dispatch Blast</button>
                    </div>
                </form>

                <div id="blast-feedback" class="result-box" aria-live="polite">
                    <p>Status:</p>
                    <strong>Belum ada request</strong>
                </div>
            </article>

            <article class="card">
                <h2>Number Checker</h2>
                <p class="muted">Validasi nomor kini terhubung ke API checker per sumber data.</p>
                <form class="form" id="number-form" onsubmit="return false;">
                    <label>
                        Nomor Telepon
                        <input id="phone_input" type="text" placeholder="08xxxxxxxxxx" autocomplete="off">
                    </label>
                    <label>
                        Sumber Data
                        <select id="source_select">
                            <option value="main_db">Main DB</option>
                            <option value="legacy_crm">Legacy CRM</option>
                            <option value="data_warehouse">Data Warehouse</option>
                        </select>
                    </label>
                    <button type="button" class="secondary" id="check-number-btn">Cek Nomor</button>
                </form>
                <div class="result-box" id="number-feedback">
                    <p>Status terakhir:</p>
                    <strong>Belum ada pengecekan</strong>
                </div>
            </article>

            <article class="card card-wide">
                <h2>Riwayat Blast (Realtime)</h2>
                <p class="muted">Auto refresh tiap 15 detik untuk melihat progres blast terbaru.</p>
                <div class="history-toolbar">
                    <label>
                        Filter Status
                        <select id="history_status_filter">
                            <option value="">Semua</option>
                            <option value="QUEUED">QUEUED</option>
                            <option value="RUNNING">RUNNING</option>
                            <option value="DONE">DONE</option>
                            <option value="FAILED">FAILED</option>
                            <option value="PARTIAL">PARTIAL</option>
                        </select>
                    </label>
                    <label>
                        Limit
                        <input id="history_limit_filter" type="number" min="1" max="100" value="20">
                    </label>
                    <button type="button" class="secondary" id="history-refresh-btn">Refresh</button>
                </div>
                <div class="table-wrap" id="history-region" aria-live="polite">
                    <table class="history-table" id="blast-history-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Requested By</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7">Belum ada data.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>

    <div id="toast" class="toast" role="status" aria-live="polite" aria-atomic="true"></div>

    <script>
        const blastForm = document.getElementById('blast-form');
        const feedback = document.getElementById('blast-feedback');
        const submitBtn = document.getElementById('submit-btn');


        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }


        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            toast.className = `toast show ${type}`;
            toast.textContent = message;

            clearTimeout(showToast.timer);
            showToast.timer = setTimeout(() => {
                toast.className = 'toast';
            }, 3000);
        }

        function renderHistorySkeleton() {
            const tableBody = document.querySelector('#blast-history-table tbody');
            tableBody.innerHTML = Array.from({ length: 4 })
                .map(() => `
                    <tr>
                        <td colspan="7"><div class="skeleton-line"></div></td>
                    </tr>
                `)
                .join('');
        }

        blastForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const productId = Number(document.getElementById('product_id').value);
            const category = document.getElementById('category').value;
            const requestedBy = document.getElementById('requested_by').value.trim();
            const payloadText = document.getElementById('payload_json').value;

            let payload;
            try {
                payload = JSON.parse(payloadText);
            } catch (error) {
                feedback.className = 'result-box error';
                feedback.innerHTML = '<p>Status:</p><strong>Payload JSON tidak valid.</strong>';
                return;
            }

            submitBtn.disabled = true;
            submitBtn.setAttribute('aria-busy', 'true');
            submitBtn.textContent = 'Mengirim...';

            try {
                const response = await fetch('/api/blast/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        category,
                        payload,
                        requested_by: requestedBy || null,
                    }),
                });

                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Request gagal diproses');
                }

                feedback.className = 'result-box success';
                feedback.innerHTML = `<p>Status:</p><strong>${escapeHtml(result.message)} (Job ID: ${escapeHtml(result.blast_job_id)})</strong>`;
                showToast('Blast berhasil di-queue.', 'success');
                await loadBlastHistory();
            } catch (error) {
                feedback.className = 'result-box error';
                feedback.innerHTML = `<p>Status:</p><strong>${escapeHtml(error.message)}</strong>`;
                showToast('Blast gagal diproses.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.removeAttribute('aria-busy');
                submitBtn.textContent = 'Dispatch Blast';
            }
        });

        const numberFeedback = document.getElementById('number-feedback');
        document.getElementById('check-number-btn').addEventListener('click', async () => {
            const raw = document.getElementById('phone_input').value;
            const source = document.getElementById('source_select').value;

            if (!raw.trim()) {
                numberFeedback.className = 'result-box error';
                numberFeedback.innerHTML = '<p>Status terakhir:</p><strong>Nomor telepon wajib diisi.</strong>';
                return;
            }

            numberFeedback.className = 'result-box';
            numberFeedback.innerHTML = '<p>Status terakhir:</p><strong>Mengecek nomor...</strong>';

            try {
                const response = await fetch('/api/number/check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ number: raw, source }),
                });

                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Pengecekan gagal.');
                }

                const sourceName = result.sources?.[0]?.source_db ?? '-';
                const exists = result.sources?.[0]?.exists ? 'Ya' : 'Tidak';

                numberFeedback.className = 'result-box success';
                numberFeedback.innerHTML = `<p>Status terakhir:</p><strong>Normalized: ${escapeHtml(result.normalized_number)} | Source: ${escapeHtml(sourceName)} | Ada: ${escapeHtml(exists)}</strong>`;
                showToast('Number checker selesai.', 'success');
            } catch (error) {
                numberFeedback.className = 'result-box error';
                numberFeedback.innerHTML = `<p>Status terakhir:</p><strong>${escapeHtml(error.message)}</strong>`;
                showToast('Number checker gagal.', 'error');
            }
        });


        document.getElementById('format-json-btn').addEventListener('click', () => {
            const payloadElement = document.getElementById('payload_json');

            try {
                const parsed = JSON.parse(payloadElement.value || '{}');
                payloadElement.value = JSON.stringify(parsed, null, 2);
                showToast('Payload JSON berhasil dirapikan.', 'success');
            } catch (error) {
                showToast('Payload JSON belum valid, tidak bisa diformat.', 'error');
            }
        });

        async function loadBlastHistory() {
            const tableBody = document.querySelector('#blast-history-table tbody');
            const historyRegion = document.getElementById('history-region');
            const statusFilter = document.getElementById('history_status_filter').value;
            const limitFilter = document.getElementById('history_limit_filter').value;

            renderHistorySkeleton();
            historyRegion.setAttribute('aria-busy', 'true');

            try {
                const params = new URLSearchParams();
                if (statusFilter) {
                    params.set('status', statusFilter);
                }
                if (limitFilter) {
                    params.set('limit', limitFilter);
                }

                const response = await fetch(`/api/blast/jobs?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Gagal mengambil riwayat blast.');
                }

                const items = result.items || [];

                document.getElementById('stat-total-blast').textContent = items.length;
                document.getElementById('stat-success').textContent = items.filter((item) => item.status === 'DONE').length;
                document.getElementById('stat-failed').textContent = items.filter((item) => item.status === 'FAILED').length;

                if (items.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="7">Belum ada data.</td></tr>';
                    return;
                }

                tableBody.innerHTML = items
                    .map((item) => {
                        const progress = `${item.success_groups}/${item.total_groups} success, ${item.failed_groups} failed`;
                        const progressPercent = Number(item.progress_percent || 0);
                        return `
                            <tr>
                                <td>#${escapeHtml(item.id)}</td>
                                <td>${escapeHtml(item.product_id)}</td>
                                <td>${escapeHtml(item.category)}</td>
                                <td><span class="badge badge-${String(item.status).toLowerCase()}">${escapeHtml(item.status)}</span></td>
                                <td>
                                    <div class="progress-wrap">
                                        <div class="progress-bar" style="width: ${progressPercent}%"></div>
                                    </div>
                                    <small>${escapeHtml(progress)} (${escapeHtml(progressPercent)}%)</small>
                                </td>
                                <td>${escapeHtml(item.requested_by || '-')}</td>
                                <td>${escapeHtml(item.created_at || '-')}</td>
                            </tr>
                        `;
                    })
                    .join('');
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="7">${escapeHtml(error.message)}</td></tr>`;
                showToast('Gagal mengambil riwayat blast.', 'error');
            } finally {
                historyRegion.removeAttribute('aria-busy');
            }
        }

        document.getElementById('history-refresh-btn').addEventListener('click', loadBlastHistory);
        document.getElementById('history_status_filter').addEventListener('change', loadBlastHistory);

        loadBlastHistory();
        setInterval(loadBlastHistory, 15000);
    </script>
</body>
</html>
