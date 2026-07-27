<!DOCTYPE html>
<html><body style="font-family:sans-serif;background:#f4f4f4;padding:20px">
  <div style="max-width:600px;margin:auto;background:#fff;border-radius:12px;padding:24px">
    <h2 style="color:#CFA866;margin-top:0">Nouvelle commande #{{ $order->id }}</h2>
    <p><strong>Client :</strong> {{ $order->client_name }}<br>
    <strong>Téléphone :</strong> {{ $order->client_phone }}<br>
    @if($order->client_email)
      <strong>Email :</strong> {{ $order->client_email }}<br>
    @endif
    <strong>Total :</strong> {{ number_format($order->total, 0, ',', ' ') }} FCFA<br>
    @if($order->payment_method)
      <strong>Paiement :</strong> {{ $order->payment_method }}<br>
    @endif
    @if($order->delivery_method)
      <strong>Livraison :</strong> {{ $order->delivery_method }}<br>
    @endif
    @if($order->delivery_address)
      <strong>Adresse :</strong> {{ $order->delivery_address }}<br>
    @endif
    @if($order->promo_code)
      <strong>Code promo :</strong> {{ $order->promo_code }}<br>
    @endif
    </p>
    <hr>
    <h3>Articles</h3>
    <table style="width:100%;border-collapse:collapse">
      <tr style="background:#f8f8f8"><th style="text-align:left;padding:8px">Produit</th><th style="padding:8px">Qté</th><th style="padding:8px">Prix</th></tr>
      @foreach($items as $item)
      <tr><td style="padding:8px">{{ $item['name'] }}</td><td style="padding:8px;text-align:center">{{ $item['quantity'] }}</td><td style="padding:8px">{{ number_format($item['line_total'], 0, ',', ' ') }} FCFA</td></tr>
      @endforeach
    </table>
  </div>
</body></html>
