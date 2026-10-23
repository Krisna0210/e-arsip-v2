<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Pengarsipan Dokumen</title>
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
        .hero-badge {
            display: inline-block;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 16px;
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
            /* border: 0px solid #e2e8f0; */
            border-radius: 12px;
            padding: 6px 14px;
            margin-bottom: 28px;
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
            width: 38%;
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
        .btn-container {
            text-align: center;
            margin: 30px 0 20px 0;
        }
        .btn {
            display: inline-block;
            background-color: #059669;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 36px;
            text-align: center;
        }
        .footer-text {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
            margin: 0 0 8px 0;
        }
        .footer-note {
            font-size: 11px;
            color: #cbd5e1;
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
                            <h2 class="title">Surat Resmi Diarsipkan</h2>
                            <p class="greeting">
                                Halo <strong>{{ $recipientName }}</strong>,<br>
                                Berkas fisik surat yang Anda daftarkan telah selesai dipindai (*scan*) dan resmi diarsipkan secara permanen ke dalam repositori digital oleh Petugas Kearsipan (Tapersip).
                            </p>

                            <!-- Document Details Card -->
                            <div class="details-card">
                                <table role="presentation" class="details-table" cellspacing="0" cellpadding="0">
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
                                        <td class="details-label">Tanggal Surat</td>
                                        <td class="details-value">
                                            : {{ $document->document_date ? \Carbon\Carbon::parse($document->document_date)->translatedFormat('d F Y') : '-' }}
                                        </td>
                                    </tr>
                                    @if($officer)
                                    <tr>
                                        <td class="details-label">Petugas Tapersip</td>
                                        <td class="details-value">: {{ $officer->name }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td class="details-label">Waktu Diarsipkan</td>
                                        <td class="details-value">: {{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <p class="footer-text">
                                Email ini dikirimkan secara otomatis oleh Sistem E-Arsip.<br>
                                Harap tidak membalas email ini secara langsung.
                            </p>
                            <p class="footer-note">
                                &copy; {{ date('Y') }} Sistem Kearsipan Digital Perusahaan. Hak Cipta Dilindungi.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
