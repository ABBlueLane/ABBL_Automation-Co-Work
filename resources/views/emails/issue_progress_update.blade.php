<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wdth,wght@62.5..100,100..900&display=swap"
        rel="stylesheet">
</head>

<body
    style="font-family: 'Noto Sans Thai', sans-serif; box-sizing: border-box; font-size: 14px; width: 100%; background-color: transparent; margin: 0; padding-top: 32px; padding-bottom: 32px;">
    <table
        style="font-family: 'Noto Sans Thai', sans-serif; font-size: 14px; width: 100%; background-color: transparent; margin: 0;">
        <tr>
            <td style="vertical-align: top;"></td>
            <td width="600"
                style="font-family: 'Noto Sans Thai', sans-serif; font-size: 14px; vertical-align: top; display: block !important; max-width: 600px !important; clear: both !important; margin: 0 auto;"
                valign="top">
                <div
                    style="font-family: 'Noto Sans Thai', sans-serif; font-size: 14px; max-width: 600px; display: block; margin: 0 auto; padding: 20px;">
                    <table width="100%" cellpadding="0" cellspacing="0"
                        style="font-family: 'Noto Sans Thai', sans-serif; font-size: 14px; border-radius: 3px; margin: 0; border: none;">
                        <tr>
                            <td style="font-family: 'Noto Sans Thai', sans-serif; box-sizing: border-box; color: #495057; font-size: 14px; vertical-align: top; margin: 0; padding: 30px; box-shadow: 0 6px 24px rgba(30,32,37,.10); border-radius: 16px; border: 1px solid #e9ebec; background-color: #ffffff;"
                                valign="top">
                                <table width="100%" cellpadding="0" cellspacing="0"
                                    style="font-family: 'Noto Sans Thai', sans-serif; font-size: 14px; margin: 0;">
                                    <tr>
                                        <td style="padding: 0 0 20px;" valign="top">
                                            <img src="{{ url('images/black-logo-bluelane.png') }}" alt=""
                                                height="50">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 20px; line-height: 1.5; font-weight: 500; padding: 0 0 10px;"
                                            valign="top">
                                            เรียน {{ $details['recipient_name'] ?? 'ลูกค้า' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #878a99; line-height: 1.6; font-size: 15px; padding: 0 0 18px;"
                                            valign="top">
                                            มีการอัปเดตความคืบหน้ารายการแจ้งปัญหาของท่านจากทีมงาน โดยมีรายละเอียดดังนี้
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0 0 16px;" valign="top">
                                            <table width="100%" cellpadding="0" cellspacing="0"
                                                style="background-color: #f8f9fa; border-radius: 10px; border: 1px solid #e9ebec; font-size: 14px;">
                                                <tr>
                                                    <td style="padding: 16px 20px;">
                                                        <table width="100%" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td
                                                                    style="padding: 5px 0; color: #495057; font-weight: 600; width: 36%;">
                                                                    หมายเลขเคส</td>
                                                                <td style="padding: 5px 0; color: #878a99;">
                                                                    {{ $details['issue_number'] ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td
                                                                    style="padding: 5px 0; color: #495057; font-weight: 600;">
                                                                    หัวข้อ</td>
                                                                <td style="padding: 5px 0; color: #878a99;">
                                                                    {{ $details['issue_title'] ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td
                                                                    style="padding: 5px 0; color: #495057; font-weight: 600;">
                                                                    สถานะล่าสุด</td>
                                                                <td style="padding: 5px 0;">
                                                                    <span
                                                                        style="display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; color: #ffffff; background-color: {{ $details['status_color'] ?? '#405189' }};">
                                                                        {{ $details['status_label'] ?? '-' }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td
                                                                    style="padding: 5px 0; color: #495057; font-weight: 600;">
                                                                    ระดับ SLA</td>
                                                                <td style="padding: 5px 0; color: #878a99;">
                                                                    {{ $details['priority_label'] ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td
                                                                    style="padding: 5px 0; color: #495057; font-weight: 600;">
                                                                    อัปเดตโดย</td>
                                                                <td style="padding: 5px 0; color: #878a99;">
                                                                    {{ $details['updated_by'] ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td
                                                                    style="padding: 5px 0; color: #495057; font-weight: 600;">
                                                                    วันที่อัปเดต</td>
                                                                <td style="padding: 5px 0; color: #878a99;">
                                                                    {{ $details['updated_at'] ?? '-' }}</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="padding: 0 0 8px; color: #495057; font-weight: 600; font-size: 15px;"
                                            valign="top">
                                            รายละเอียดความคืบหน้า
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0 0 18px;" valign="top">
                                            <div
                                                style="background-color: #f8f9fa; border: 1px solid #e9ebec; border-radius: 10px; padding: 16px 18px; color: #495057; line-height: 1.8; white-space: pre-line;">
                                                {{ $details['comment'] ?? '-' }}
                                            </div>
                                        </td>
                                    </tr>

                                    @if (!empty($details['image_files']))
                                        <tr>
                                            <td style="padding: 0 0 8px; color: #495057; font-weight: 600; font-size: 15px;"
                                                valign="top">
                                                รูปภาพประกอบ
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 0 0 18px;" valign="top">
                                                @foreach ($details['image_files'] as $image)
                                                    <div style="padding-bottom: 12px;">
                                                        <a href="{{ $image['url'] }}" target="_blank">
                                                            <img src="{{ $image['url'] }}"
                                                                alt="{{ $image['name'] }}"
                                                                style="display: block; width: 100%; max-width: 520px; border-radius: 10px; border: 1px solid #e9ebec;">
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endif

                                    @if (!empty($details['attachment_files']))
                                        <tr>
                                            <td style="padding: 0 0 8px; color: #495057; font-weight: 600; font-size: 15px;"
                                                valign="top">
                                                ไฟล์แนบ
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 0 0 18px;" valign="top">
                                                @foreach ($details['attachment_files'] as $file)
                                                    <div style="padding-bottom: 8px;">
                                                        <a href="{{ $file['url'] }}" target="_blank"
                                                            style="color: #405189; text-decoration: none;">
                                                            {{ $file['name'] }}
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endif

                                    <tr>
                                        <td style="padding: 0 0 24px;" valign="top">
                                            <a href="{{ $details['view_url'] ?? '#' }}" target="_blank"
                                                style="display: inline-block; padding: 12px 20px; background-color: #405189; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600;">
                                                ดูรายละเอียดเคส
                                            </a>
                                        </td>
                                    </tr>

                                    <tr style="border-top: 1px solid #e9ebec;">
                                        <td style="padding-top: 15px;" valign="top">
                                            <p style="margin: 0 0 4px; color: #878a99; font-size: 14px;">
                                                ขอแสดงความนับถือ</p>
                                            <p style="margin: 0; font-weight: 600; color: #495057; font-size: 15px;">
                                                {{ $details['business_name'] ?? 'OneClick' }}</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <div style="text-align: center; margin: 0 auto;">
                        <p
                            style="font-family: 'Noto Sans Thai', sans-serif; font-size: 14px; color: #595f63; margin: 0; padding-top: 25px;">
                            2024 OneClick. Design & Develop by BlueLane
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>

</html>
