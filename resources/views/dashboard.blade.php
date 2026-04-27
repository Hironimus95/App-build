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
                    <strong>12</strong>
                </article>
                <article>
                    <p>Group Aktif</p>
                    <strong>28</strong>
                </article>
                <article>
                    <p>Success Rate</p>
                    <strong>97%</strong>
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
                <p class="muted">Mock UX untuk validasi nomor. Integrasi endpoint bisa dilanjutkan pada iterasi berikutnya.</p>
                <form class="form" id="number-form" onsubmit="return false;">
                    <label>
                        Nomor Telepon
                        <input id="phone_input" type="text" placeholder="08xxxxxxxxxx" autocomplete="off">
                    </label>
                    <label>
                        Sumber Data
                        <select>
                            <option>Main DB</option>
                            <option>Legacy CRM</option>
                            <option>Data Warehouse</option>
                        </select>
                    </label>
                    <button type="button" class="secondary" id="check-number-btn">Cek Nomor</button>
                </form>
                <div class="result-box" id="number-feedback">
                    <p>Status terakhir:</p>
                    <strong>Belum ada pengecekan</strong>
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
                        'Accept': 'application/json',
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
            } catch (error) {
                feedback.className = 'result-box error';
                feedback.innerHTML = `<p>Status:</p><strong>${error.message}</strong>`;
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Dispatch Blast';
            }
        });

        const numberFeedback = document.getElementById('number-feedback');
        document.getElementById('check-number-btn').addEventListener('click', () => {
            const raw = document.getElementById('phone_input').value;
            const normalized = raw.replace(/\D/g, '');
            numberFeedback.className = 'result-box';
            numberFeedback.innerHTML = `<p>Status terakhir:</p><strong>Preview normalized: ${normalized || '-'}</strong>`;
        });
    </script>
</body>
</html>
