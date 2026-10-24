<p class="font-semibold text-ink" x-text="details?.title"></p>
<div>
    <label for="detail-mistakes" class="mb-1 block text-sm font-medium text-ink">Jumlah kesalahan</label>
    <input id="detail-mistakes" type="text" inputmode="numeric" x-model="details.raw" class="w-full rounded-lg border border-line px-3 py-2" />
</div>
<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label for="detail-initials" class="mb-1 block text-sm font-medium text-ink">Paraf</label>
        <input id="detail-initials" type="text" maxlength="30" x-model="details.initials" class="w-full rounded-lg border border-line px-3 py-2" />
    </div>
    <div>
        <label for="detail-assessed-at" class="mb-1 block text-sm font-medium text-ink">Tanggal penilaian</label>
        <input id="detail-assessed-at" type="date" x-model="details.assessedAt" class="w-full rounded-lg border border-line px-3 py-2" />
    </div>
</div>
<div>
    <label for="detail-note" class="mb-1 block text-sm font-medium text-ink">Catatan</label>
    <textarea id="detail-note" maxlength="500" rows="3" x-model="details.note" class="w-full rounded-lg border border-line px-3 py-2"></textarea>
</div>
<p x-show="detailsError" x-text="detailsError" role="alert" class="text-sm text-danger"></p>
