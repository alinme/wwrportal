@php
    $school_name = $school_name ?? '';
    $structure_name = $structure_name ?? '';
    $group_name = $group_name ?? '';
    $educator_name = $educator_name ?? '';
    $state_county = $state_county ?? '';
    $city = $city ?? '';
    $address = $address ?? '';
    $facilitator = $facilitator ?? '';
    $distribution_date = $distribution_date ?? '';
    $rows = $rows ?? [];
@endphp
<div style="width: 100%;"></div>
    <p style="text-align: center; line-height: 1; margin-bottom: 10px; ">
        <h3 style="margin: 0;text-align: center;">TABEL NOMINAL</h3>
        <h3 style="margin: 0;text-align: center;">LISTA DE DISTRIBUȚIE KIT-URI EDUCAȚIONALE</h3>
    </p>
    <p style="text-align: center; line-height: 1; margin-bottom: 10px; ">
        <div style="margin-bottom: 6px;">Proiect „ <em>Start în educație</em> ”</div>
        <div style="margin-bottom: 6px;">Unitatea de învățământ <strong><em>{{ $school_name }}</em></strong>@if($structure_name) / <strong><em>{{ $structure_name }}</em></strong>@endif / <strong><em>{{ $group_name }}</em></strong></div>
        <div style="margin-bottom: 6px;">Adresa: <strong><em>{{ $address }}</em></strong> / Județ <strong><em>{{ $state_county }}</em></strong> Comună/ Oraș <strong><em>{{ $city }}</em></strong></div>
        <div style="margin-bottom: 6px;">Nume și prenume responsabil distribuție: facilitator / reprezentant școală <strong><em>{{ $facilitator }}</em></strong> / {{ $educator_name }}</div>
        <div style="margin-bottom: 6px;">Data ................. <strong><em>{{ $distribution_date }}</em></strong></div>
        <div style="margin-bottom: 6px;">Conținut kit „Ghiozdănel cu viitor”: <em><small>ghiozdan grădiniță, etichetă nume, carte de povești Prima mea lectură, creioane colorate 12 culori, creion grafit HB, caiet de lucru editura Litera matematică, caiet de lucru editura Litera modele de scriere, caiet A5 velin, numărătoare, puzzle harta României, coardă pentru sărit, puzzle din lemn, frisbee, joc „aruncă și prinde”, acuarele 12 culori și pensulă, set 4 pensule nr. 3/5/7/9, bloc desen A4- 2 buc, plastilină 10 culori, sticker diverse modele (litere colorate, cifre colorate), radieră, lipici stick, riglă 15 cm, planșetă modelaj cu accesorii, forme de modelat plastilina, pastă dinți, periuță dinți, săpun, scrisoare pentru părinte.</small> </em> </div>
    </p>
</div>

<div>
    <table style="width: 100%; border-collapse: collapse; border: 1px solid black;">
        <thead style="background-color: #f0f0f0;">
            <tr>
                <th style="border: 1px solid black; padding: 8px; width: 10%;">Nr. crt.</th>
                <th style="border: 1px solid black; padding: 8px; width: 30%;">Nume şi prenume copil</th>
                <th style="border: 1px solid black; padding: 8px; width: 30%;">Nume şi prenume părinte/tutore</th>
                <th style="border: 1px solid black; padding: 8px; width: 30%;">Semnătură</th>
            </tr>
        </thead>
        <tbody style="border: 1px solid black; padding: 8px;">
            @foreach($rows as $row)
            <tr>
                <td style="border: 1px solid black; padding: 8px; text-align: center;">{{ $row['number'] }}</td>
                <td style="border: 1px solid black; padding: 8px; text-align: center;">{{ $row['child_name'] }}</td>
                <td style="border: 1px solid black; padding: 8px; text-align: center;">{{ $row['parent_name'] }}</td>
                <td style="border: 1px solid black; padding: 8px; text-align: center;"></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
