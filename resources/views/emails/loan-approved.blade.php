<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Persetujuan Peminjaman Berkas</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        td {
            padding: 0;
        }
        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f1f5f9;
            padding: 40px 0;
        }
        .main-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #059669;
            padding: 28px 36px;
            text-align: left;
        }
        .header-title {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .header-subtitle {
            color: #d1fae5;
            font-size: 12px;
            margin: 4px 0 0 0;
            font-weight: 500;
        }
        .content {
            padding: 36px;
        }
        .title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 12px 0;
            line-height: 1.3;
        }
        .greeting {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            margin: 0 0 24px 0;
        }
        .details-card {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 6px 14px;
            margin-bottom: 8px;
        }
        .details-table {
            width: 100%;
        }
        .details-table td {
            padding: 6px 0;
            font-size: 13px;
            vertical-align: top;
        }
        .details-label {
            color: #64748b;
            width: 40%;
            font-weight: 600;
        }
        .details-value {
            color: #1e293b;
            font-weight: 500;
        }
        .details-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #059669;
            font-weight: 700;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <table role="presentation" class="wrapper" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" class="main-container" width="100%" cellspacing="0" cellpadding="0">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <h1 class="header-title">E-Arsip</h1>
                            <p class="header-subtitle">Sistem Manajemen Kearsipan Digital</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td class="content">
                            <h2 class="title">Peminjaman Berkas Disetujui</h2>
                            <p class="greeting">
                                Halo <strong>{{ $recipientName }}</strong>,<br>
                                Permohonan peminjaman berkas fisik dokumen Anda telah disetujui mohon menuju ke Tapersip untuk mengambil berkas.
                            </p>

                            <!-- Document Details Card -->
                            <div class="details-card">
                                <table role="presentation" class="details-table" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="details-label">Kode Tiket</td>
                                        <td class="details-value details-mono">: {{ $loan->loan_code }}</td>
                                    </tr>
                                    <tr>
                                        <td class="details-label">Nomor Dokumen</td>
                                        <td class="details-value details-mono">: {{ $document->number ?? 'Tanpa Nomor' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="details-label">Jenis Dokumen</td>
                                        <td class="details-value">: {{ $documentType }}</td>
                                    </tr>
                                    <tr>
                                        <td class="details-label">Perihal</td>
                                        <td class="details-value">: {{ $document->subject }}</td>
                                    </tr>
                                    <tr>
                                        <td class="details-label">Tanggal Pinjam</td>
                                        <td class="details-value">
                                            : {{ $loan->borrow_date ? \Carbon\Carbon::parse($loan->borrow_date)->translatedFormat('d F Y') : '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="details-label">Batas Pengembalian</td>
                                        <td class="details-value">
                                            : {{ $loan->expected_return_date ? \Carbon\Carbon::parse($loan->expected_return_date)->translatedFormat('d F Y') : '-' }}
                                        </td>
                                    </tr>
                                    @if($officer)
                                    <tr>
                                        <td class="details-label">Petugas Tapersip</td>
                                        <td class="details-value">: {{ $officer->name }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="details-label">Waktu Disetujui</td>
                                        <td class="details-value">: {{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
