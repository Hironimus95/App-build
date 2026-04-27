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

                    <button type="submit" id="submit-btn">Dispatch Blast</button>
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
                <div class="table-wrap">
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

    <script>
        const blastForm = document.getElementById('blast-form');
        const feedback = document.getElementById('blast-feedback');
        const submitBtn = document.getElementById('submit-btn');

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
                feedback.innerHTML = `<p>Status:</p><strong>${result.message} (Job ID: ${result.blast_job_id})</strong>`;
                await loadBlastHistory();
            } catch (error) {
                feedback.className = 'result-box error';
                feedback.innerHTML = `<p>Status:</p><strong>${error.message}</strong>`;
            } finally {
                submitBtn.disabled = false;
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
                numberFeedback.innerHTML = `<p>Status terakhir:</p><strong>Normalized: ${result.normalized_number} | Source: ${sourceName} | Ada: ${exists}</strong>`;
            } catch (error) {
                numberFeedback.className = 'result-box error';
                numberFeedback.innerHTML = `<p>Status terakhir:</p><strong>${error.message}</strong>`;
            }
        });

        async function loadBlastHistory() {
            const tableBody = document.querySelector('#blast-history-table tbody');

            try {
                const response = await fetch('/api/blast/jobs', {
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
                        return `
                            <tr>
                                <td>#${item.id}</td>
                                <td>${item.product_id}</td>
                                <td>${item.category}</td>
                                <td><span class="badge badge-${String(item.status).toLowerCase()}">${item.status}</span></td>
                                <td>${progress}</td>
                                <td>${item.requested_by || '-'}</td>
                                <td>${item.created_at || '-'}</td>
                            </tr>
                        `;
                    })
                    .join('');
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="7">${error.message}</td></tr>`;
            }
        }

        loadBlastHistory();
        setInterval(loadBlastHistory, 15000);
    </script>
</body>
</html>
