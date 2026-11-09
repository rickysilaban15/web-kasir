<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LARIS — Admin Licensing & Entitlement Console</title>
    <style>
        :root {
            --primary: #1B4D3E;
            --primary-dark: #153D32;
            --primary-surface: #EBF2EF;
            --accent: #FCD804;
            --bg: #FAF9F6;
            --surface: #FFFFFF;
            --text-primary: #202522;
            --text-secondary: #69736D;
            --border: #E5E9E5;
            --success: #23865B;
            --warning: #B7791F;
            --danger: #C44747;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg);
            color: var(--text-primary);
            line-height: 1.5;
        }
        header {
            background-color: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-badge {
            width: 34px;
            height: 34px;
            background-color: var(--primary);
            color: #FFFFFF;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        .alert {
            background-color: var(--primary-surface);
            border: 1px solid var(--primary);
            color: var(--primary-dark);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .card {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
        }
        .metric-label {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .metric-value {
            font-size: 28px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }
        .layout-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 900px) {
            .layout-grid { grid-template-columns: 1fr; }
        }
        h2 { font-size: 16px; font-weight: 700; margin-bottom: 12px; }
        .form-group { margin-bottom: 12px; }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        input, select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            background-color: var(--surface);
        }
        .btn {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
        }
        .btn-primary {
            background-color: var(--primary);
            color: #FFFFFF;
            width: 100%;
        }
        .btn-danger {
            background-color: var(--surface);
            border-color: var(--danger);
            color: var(--danger);
            padding: 5px 10px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid var(--border);
            font-variant-numeric: tabular-nums;
        }
        th { color: var(--text-secondary); font-weight: 600; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            background-color: var(--primary-surface);
            color: var(--primary);
        }
        .badge-revoked {
            background-color: #FDECEC;
            color: var(--danger);
        }
        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <header>
        <div class="brand">
            <div class="brand-badge">L</div>
            <div>
                <div style="font-weight: 700;">LARIS Licensing & Admin Console</div>
                <div style="font-size: 12px; color: var(--text-secondary);">Server Otoritatif Lisensi, Paket (FREE/PRO/BUSINESS), & Aktivasi Perangkat</div>
            </div>
        </div>
        <div class="badge">Ed25519 Signed Tokens</div>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif

        <section class="metrics-grid">
            <div class="card">
                <div class="metric-label">Total Akun Terdaftar</div>
                <div class="metric-value">{{ $metrics['total_users'] }}</div>
            </div>
            <div class="card">
                <div class="metric-label">Total Lisensi Diterbitkan</div>
                <div class="metric-value">{{ $metrics['total_licenses'] }}</div>
            </div>
            <div class="card">
                <div class="metric-label">Lisensi Berstatus Aktif</div>
                <div class="metric-value">{{ $metrics['active_licenses'] }}</div>
            </div>
            <div class="card">
                <div class="metric-label">Perangkat POS Teraktivasi</div>
                <div class="metric-value">{{ $metrics['active_devices'] }}</div>
            </div>
        </section>

        <section class="layout-grid">
            <div class="card">
                <h2>Terbitkan Lisensi Baru</h2>
                <form method="POST" action="{{ route('admin.licenses.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="package_code">Paket Langganan</label>
                        <select id="package_code" name="package_code" required>
                            @foreach ($packages as $pkg)
                                <option value="{{ $pkg->code }}">{{ $pkg->name }} ({{ $pkg->code }}) — {{ $pkg->features_count }} Fitur</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="duration_days">Durasi Masa Aktif (Hari)</label>
                        <input id="duration_days" type="number" name="duration_days" value="365" min="1" max="3650" required>
                    </div>
                    <div class="form-group">
                        <label for="max_devices">Batas Maksimal Perangkat</label>
                        <input id="max_devices" type="number" name="max_devices" value="2" min="1" max="50">
                    </div>
                    <div class="form-group">
                        <label for="notes">Catatan Pelanggan / Outlet</label>
                        <input id="notes" type="text" name="notes" placeholder="Contoh: Outlet Kopi Nusantara">
                    </div>
                    <button type="submit" class="btn btn-primary">Generate License Key</button>
                </form>
            </div>

            <div class="card">
                <h2>Daftar Lisensi & Perangkat</h2>
                <table>
                    <thead>
                        <tr>
                            <th>License Key</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th>Perangkat</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($licenses as $lic)
                            <tr>
                                <td><code>{{ $lic->license_key }}</code></td>
                                <td><span class="badge">{{ $lic->package?->code ?? 'FREE' }}</span></td>
                                <td>
                                    <span class="badge {{ $lic->status === 'revoked' ? 'badge-revoked' : '' }}">
                                        {{ strtoupper($lic->status) }}
                                    </span>
                                </td>
                                <td>{{ $lic->activeActivations->count() }} / {{ $lic->max_devices }}</td>
                                <td>{{ $lic->notes ?: '-' }}</td>
                                <td>
                                    @if ($lic->status !== 'revoked')
                                        <form method="POST" action="{{ route('admin.licenses.extend', $lic) }}" style="display:inline; margin-right: 4px;">
                                            @csrf
                                            <input type="hidden" name="additional_days" value="30">
                                            <button type="submit" class="btn" style="border-color: var(--primary); color: var(--primary); padding: 5px 10px; font-size: 12px; background-color: var(--surface);">+30 Hari</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.licenses.revoke', $lic) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger">Revoke</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="color: var(--text-secondary);">Belum ada lisensi diterbitkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card" style="margin-bottom: 24px;">
            <h2>Perangkat POS Teraktivasi Terbaru</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nama Perangkat</th>
                        <th>Installation ID</th>
                        <th>Lisensi & Paket</th>
                        <th>Status</th>
                        <th>Validasi Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentActivations as $act)
                        <tr>
                            <td>{{ $act->device_name }} ({{ $act->device_model ?: 'Android POS' }})</td>
                            <td><code>{{ $act->device_installation_id }}</code></td>
                            <td>
                                <code>{{ $act->license?->maskedKey() }}</code>
                                <span class="badge">{{ $act->license?->package?->code ?? 'FREE' }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $act->status !== 'active' ? 'badge-revoked' : '' }}">
                                    {{ strtoupper($act->status) }}
                                </span>
                            </td>
                            <td>{{ $act->last_validated_at?->format('Y-m-d H:i') ?: '-' }}</td>
                            <td>
                                @if ($act->status === 'active')
                                    <form method="POST" action="{{ route('admin.activations.deactivate', $act) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Lepas Slot</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="color: var(--text-secondary);">Belum ada perangkat POS yang mengaktifkan lisensi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="layout-grid">
            <div class="card">
                <h2>Manajemen Paket & Batas Perangkat</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Fitur</th>
                            <th>Maks. Device</th>
                            <th>Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packages as $pkg)
                            <tr>
                                <td><span class="badge">{{ $pkg->code }}</span></td>
                                <td>{{ $pkg->features_count }}</td>
                                <td>{{ $pkg->max_devices }}</td>
                                <td>{{ $pkg->default_duration_days }} hr</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Manajemen Akun Pengguna</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Peran</th>
                            <th>Status</th>
                            <th>Lisensi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $usr)
                            <tr>
                                <td>{{ $usr->name }}</td>
                                <td><code>{{ $usr->email }}</code></td>
                                <td>{{ strtoupper($usr->role) }}</td>
                                <td>
                                    <span class="badge {{ $usr->status !== 'active' ? 'badge-revoked' : '' }}">
                                        {{ strtoupper($usr->status) }}
                                    </span>
                                </td>
                                <td>{{ $usr->licenses_count }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $usr) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">
                                            {{ $usr->status === 'active' ? 'Suspend' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="color: var(--text-secondary);">Belum ada akun pengguna terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Audit Log Keamanan Terbaru</h2>
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Aksi</th>
                        <th>Target</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentAuditLogs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td><code>{{ $log->action }}</code></td>
                            <td>{{ $log->target_type }} #{{ $log->target_id }}</td>
                            <td>{{ $log->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="color: var(--text-secondary);">Belum ada catatan audit log.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>

