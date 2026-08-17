<!DOCTYPE html>
<html>
<body style="margin:0; padding:0; background-color:#f7f5f2; font-family: -apple-system, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f5f2; padding: 32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border:1px solid #e4dfd6;">
                    <tr>
                        <td style="padding: 32px 40px 24px; border-bottom:1px solid #e4dfd6;">
                            <p style="margin:0; font-size:11px; font-weight:700; letter-spacing:0.3em; text-transform:uppercase; color:#8a7f6c;">Drenla</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 40px;">
                            <p style="margin:0 0 8px; font-size:11px; font-weight:700; letter-spacing:0.2em; text-transform:uppercase; color:#8a7f6c;">Proposal &middot; {{ $proposal->reference_number }}</p>
                            <h1 style="margin:0 0 16px; font-size:22px; font-weight:400; color:#161219;">{{ $proposal->title }}</h1>
                            <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#3a3327;">
                                A new proposal is ready for your review. It's attached as a PDF, and you can also view it — along with everything else in your engagement — in your Drenla client portal.
                            </p>
                            <a href="{{ $portalUrl }}" style="display:inline-block; background-color:#161219; color:#ffffff; padding:12px 24px; font-size:11px; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; text-decoration:none;">
                                View in portal
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px; border-top:1px solid #e4dfd6;">
                            <p style="margin:0; font-size:11px; color:#a49a86;">Drenla &middot; Studio Delivery Portal</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
