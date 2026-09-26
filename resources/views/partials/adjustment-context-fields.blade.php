<div class="field" data-adjustment-field="weight" hidden>
    <label>Berat estimasi (gram)</label>
    <input class="input" type="number" name="estimated_weight_grams" min="1" placeholder="Contoh: 500">
</div>
<div class="field" data-adjustment-field="weight" hidden>
    <label>Berat aktual (gram)</label>
    <input class="input" type="number" name="actual_weight_grams" min="1" placeholder="Contoh: 700">
</div>
<div class="field" data-adjustment-field="rate" hidden>
    <label>Rate awal</label>
    <input class="input" type="number" step="0.0001" min="0.0001" name="original_rate" value="{{ $defaultRate ?? '' }}">
</div>
<div class="field" data-adjustment-field="rate" hidden>
    <label>Rate akhir</label>
    <input class="input" type="number" step="0.0001" min="0.0001" name="final_rate">
</div>
<div class="field" data-adjustment-field="shipping" hidden>
    <label>Shipping estimasi (Rp)</label>
    <input class="input" type="number" min="0" name="estimated_shipping_idr" placeholder="Opsional">
</div>
<div class="field" data-adjustment-field="shipping" hidden>
    <label>Shipping aktual (Rp)</label>
    <input class="input" type="number" min="0" name="actual_shipping_idr" placeholder="Nominal aktual">
</div>
