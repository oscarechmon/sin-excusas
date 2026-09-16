<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Código de verificación</title>
</head>
<body style="margin:0; padding:0; background:#FAF6EE; font-family:Arial, Helvetica, sans-serif; color:#14110C;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAF6EE; padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border:1px solid #E8DFCF;">
          <tr>
            <td style="background:#14110C; padding:24px; text-align:center;">
              <span style="color:#D8B879; font-size:18px; letter-spacing:4px;">SIN EXCUSAS</span>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 28px;">
              <p style="margin:0 0 16px; font-size:16px;">Hola, {{ $user->name }}:</p>
              <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#4A4340;">
                Para terminar de crear tu cuenta en la tienda de Sin Excusas, ingresa este código:
              </p>
              <p style="margin:0 0 24px; text-align:center;">
                <span style="display:inline-block; padding:16px 28px; background:#FAF6EE; border:1px solid #D8B879; font-size:32px; font-weight:bold; letter-spacing:10px; color:#14110C;">{{ $code }}</span>
              </p>
              <p style="margin:0 0 8px; font-size:14px; color:#7C7263;">El código vence en {{ $minutes }} minutos.</p>
              <p style="margin:0; font-size:14px; color:#7C7263;">Si no creaste una cuenta, ignora este correo.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 28px; border-top:1px solid #E8DFCF; font-size:12px; color:#7C7263; text-align:center;">
              © {{ date('Y') }} Sin Excusas Centro Estético
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
