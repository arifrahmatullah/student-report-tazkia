import $ from 'jquery';
import select2 from 'select2';

window.$ = window.jQuery = $;
select2($);

const BATAS_CARI = 8; // kotak cari hanya muncul bila pilihannya lebih dari ini

const bahasa = {
    noResults: () => 'Tidak ada hasil',
    searching: () => 'Mencari…',
    inputTooShort: () => 'Ketik untuk mencari',
    errorLoading: () => 'Hasil tidak dapat dimuat',
};

/** Pasang Select2 pada satu <select class="field">; aman dipanggil berulang. */
export function pasang(el) {
    if (el.dataset.s2 === '1' || el.multiple) return;
    el.dataset.s2 = '1';

    const $el = $(el);
    const banyak = el.options.length > BATAS_CARI;

    $el.select2({
        width: '100%',
        language: bahasa,
        minimumResultsForSearch: banyak ? 0 : Infinity,
        placeholder: el.querySelector('option[value=""]')?.textContent || undefined,
        // Daftar dibuka di <body> agar tidak terpotong kartu / overflow; kelas tema ikut ke sana.
        dropdownParent: $(document.body),
        selectionCssClass: el.classList.contains('field-error') ? 's2-error' : '',
    });

    // Select2 hanya memicu event jQuery; Alpine (x-model, @change) mendengarkan event DOM asli.
    $el.on('select2:select select2:unselect select2:clear', () => {
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    });

    $el.on('select2:open', () => {
        document.querySelector('.select2-container--open .select2-search__field')?.focus();
    });
}

/** Sinkronkan tampilan Select2 setelah nilai diubah lewat kode (mis. oleh Alpine). */
export function segarkan(el) {
    if (el.dataset.s2 === '1') $(el).trigger('change.select2');
}

export function pasangSemua(akar = document) {
    akar.querySelectorAll('select.field').forEach(pasang);
}

/** Hubungkan ke Alpine: pasang saat elemen diinisialisasi, dan ikuti perubahan x-model. */
export function hubungkanAlpine(Alpine) {
    Alpine.interceptInit(Alpine.skipDuringClone((el) => {
        if (!(el instanceof HTMLSelectElement) || !el.classList.contains('field')) return;

        // Setelah Alpine mengisi nilai awal, baru Select2 dipasang agar menampilkan pilihan yang benar.
        Alpine.nextTick(() => {
            pasang(el);

            const model = el.getAttribute('x-model');
            if (model) {
                const baca = Alpine.evaluateLater(el, model);
                Alpine.effect(() => {
                    baca();
                    Alpine.nextTick(() => segarkan(el));
                });
            }
        });
    }));
}
