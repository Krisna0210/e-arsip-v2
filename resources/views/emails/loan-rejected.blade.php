<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Penolakan Peminjaman Berkas</title>
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
            background-color: #be123c;
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
            color: #ffe4e6;
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
            margin-bottom: 16px;
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
            color: #be123c;
            font-weight: 700;
            font-size: 14px;
        }
        .reason-box {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-left: 4px solid #e11d48;
            border-radius: 8px;
            padding: 14px 18px;
            margin-top: 16px;
        }
        .reason-title {
            font-size: 12px;
            font-weight: 700;
            color: #9f1239;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 4px 0;
        }
        .reason-content {
            font-size: 13px;
            color: #881337;
            line-height: 1.5;
            margin: 0;
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
                            <h2 class="title">Permohonan Pinjam Berkas Ditolak</h2>
                            <p class="greeting">
                                Halo <strong>{{ $recipientName }}</strong>,<br>
                                Mohon maaf, permohonan peminjaman berkas fisik dokumen Anda tidak dapat disetujui oleh Petugas Layanan Tapersip saat ini.
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
                                        <td class="details-label">Tanggal Pengajuan</td>
                                        <td class="details-value">
                                            : {{ $loan->borrow_date ? \Carbon\Carbon::parse($loan->borrow_date)->translatedFormat('d F Y') : '-' }}
                                        </td>
                                    </tr>
                                    @if($officer)
                                    <tr>
                                        <td class="details-label">Petugas Tapersip</td>
                                        <td class="details-value">: {{ $officer->name }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="details-label">Waktu Keputusan</td>
                                        <td class="details-value">: {{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Alasan Penolakan -->
                            <div class="reason-box">
                                <div class="reason-title">Alasan Penolakan:</div>
                                <p class="reason-content">{{ $reason }}</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
