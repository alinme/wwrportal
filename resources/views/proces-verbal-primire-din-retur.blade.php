@php
    $school_name = $school_name ?? '';
    $campaign_name = $campaign_name ?? '';
    $facilitator = $facilitator ?? '';
    $date = $date ?? now()->format('d.m.Y');
    $kits_count = (int) ($kits_count ?? 0);
    $address = $address ?? '';
    $city = $city ?? '';
    $state = $state ?? '';
@endphp
<div style="font-family: Arial, sans-serif; font-size: 11pt;">
    <p style="text-align: center; margin-bottom: 24px;">
        <strong>PROCES VERBAL DE PRIMIRE DIN RETUR</strong>
    </p>
    <p style="text-align: center; margin-bottom: 16px;">
        <strong>Kituri educaționale – proiect „Start în educație”</strong>
    </p>

    <p style="margin-bottom: 12px;">În data de <strong>{{ $date }}</strong>, la sediul unității de învățământ:</p>

    <p style="margin-bottom: 8px;"><strong>Unitatea de învățământ:</strong> {{ $school_name }}</p>
    <p style="margin-bottom: 8px;"><strong>Adresă:</strong> {{ $address }}, {{ $city }}, județul {{ $state }}</p>
    <p style="margin-bottom: 16px;"><strong>Campanie / distribuție:</strong> {{ $campaign_name }}</p>

    <p style="margin-bottom: 12px;">s-a constatat primirea din retur a <strong>{{ $kits_count }}</strong> kit/uri educaționale, pentru completarea necesarului (beneficiari suplimentari / transferuri între unități în perioada dintre comandă și distribuție).</p>

    <p style="margin-bottom: 12px;">Kiturile primite din retur sunt în număr de <strong>{{ $kits_count }}</strong> bucăți și sunt predate reprezentantului unității de învățământ.</p>

    <p style="margin-top: 24px; margin-bottom: 8px;">Nume și prenume responsabil distribuție (reprezentant școală / facilitator): <strong>{{ $facilitator }}</strong></p>
    <p style="margin-bottom: 24px;">Semnătură: _________________________</p>

    <p style="margin-bottom: 8px;">Data: _________________________</p>
</div>
