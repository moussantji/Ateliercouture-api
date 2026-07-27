<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><style>body{font-family:sans-serif;background:#f4f4f4;padding:20px}.card{background:#fff;border-radius:12px;padding:24px;max-width:600px;margin:0 auto}h1{color:#CFA866;font-size:22px;margin:0 0 8px}.status{display:inline-block;padding:4px 14px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:16px}ul{list-style:none;padding:0}li{padding:6px 0;border-bottom:1px solid #eee;font-size:14px}strong{color:#333}.footer{margin-top:20px;font-size:12px;color:#999;text-align:center}</style></head><body>
<div class="card">
  <h1>Couture Atelier</h1>
  <p style="color:#666;margin-bottom:16px">Bonjour <strong>{{ $order->client_name }}</strong>,</p>
  <p style="color:#666">Le statut de votre commande <strong>#{{ $order->id }}</strong> a été mis à jour :</p>
  <div class="status" style="background:
    @switch($order->status)
      @case('confirmed')#5CB87A20;color:#5CB87A @break
      @case('processing')#5BA0E020;color:#5BA0E0 @break
      @case('completed')#A87DD820;color:#A87DD8 @break
      @case('cancelled')#E8686820;color:#E86868 @break
      @default#E8A23A20;color:#E8A23A
    @endswitch">
    @switch($order->status)
      @case('confirmed')✅ Confirmée @break
      @case('processing')🔄 En cours @break
      @case('completed')✅ Terminée @break
      @case('cancelled')❌ Annulée @break
      @default⏳ En attente
    @endswitch
  </div>
  <p style="color:#666">Merci de votre confiance.</p>
  <div class="footer">Couture Atelier — Bamako</div>
</div>
</body></html>
