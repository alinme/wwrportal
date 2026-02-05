@php
    $apiKey = config('services.google.places_api_key', '');
@endphp
@if($apiKey)
@once
{{-- Google Maps bootstrap loader: loads API with loading=async and enables importLibrary() - required for PlaceAutocompleteElement and avoids "loaded directly without loading=async" / legacy Autocomplete errors --}}
<script>
(function(g){
    var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;
    b=b[c]||(b[c]={});
    var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=function(){
        h||(h=new Promise(function(f,n){
            a=m.createElement("script");
            e.set("libraries",[...r]+"");
            for(k in g)e.set(k.replace(/[A-Z]/g,function(t){return"_"+t[0].toLowerCase()}),g[k]);
            e.set("callback",c+".maps."+q);
            a.src="https://maps."+c+"apis.com/maps/api/js?"+e;
            d[q]=f;
            a.onerror=function(){h=n(new Error(p+" could not load."));};
            a.nonce=m.querySelector("script[nonce]")?.nonce||"";
            m.head.append(a);
        }));
        return h;
    };
    d[l]=d[l]||function(f,...n){
        r.add(f);
        return u().then(function(){return d[l](f,...n);});
    };
})({key: @json($apiKey), v: "weekly"});
</script>
@endonce
@endif
