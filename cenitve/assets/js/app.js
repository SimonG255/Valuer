/* assets/js/app.js – interaktivnost aplikacije */

'use strict';

// ── Toast obvestila ──
const Toast = {
    container: null,
    init() {
        this.container = document.getElementById('toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            document.body.appendChild(this.container);
        }
    },
    show(msg, type = 'default', ms = 3500) {
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        const icons = { success: '✓', error: '✕', default: 'ℹ' };
        t.innerHTML = `<span>${icons[type] ?? 'ℹ'}</span> ${msg}`;
        this.container.appendChild(t);
        setTimeout(() => {
            t.style.opacity = '0';
            t.style.transition = 'opacity .3s';
            setTimeout(() => t.remove(), 320);
        }, ms);
    }
};

// ── Modal upravljanje ──
const Modal = {
    open(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('active');
            document.body.style.overflow = 'hidden';
            const firstInput = el.querySelector('input, select, textarea');
            if (firstInput) setTimeout(() => firstInput.focus(), 80);
        }
    },
    close(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('active');
            document.body.style.overflow = '';
        }
    },
    closeAll() {
        document.querySelectorAll('.modal-overlay.active').forEach(m => {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
};

// Zapri modal ob kliku ozadja ali ESC
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') Modal.closeAll();
});

// ── Custom potrditveni modal ──
function potrdiAkcijo(sporocilo, callback) {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position:fixed;inset:0;z-index:999;
        background:rgba(26,35,50,.55);backdrop-filter:blur(3px);
        display:flex;align-items:center;justify-content:center;padding:1.5rem;
    `;
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(26,35,50,.2);
                    width:100%;max-width:400px;overflow:hidden;animation:slideUp .2s ease;">
            <div style="padding:1.75rem 1.75rem 1rem;">
                <div style="font-size:2rem;margin-bottom:.75rem;">⚠️</div>
                <h3 style="font-family:'DM Serif Display',serif;font-size:1.3rem;color:#1A2332;margin-bottom:.5rem;">Potrdite brisanje</h3>
                <p style="color:#5A6E84;font-size:.95rem;">${sporocilo}</p>
            </div>
            <div style="display:flex;gap:.75rem;justify-content:flex-end;padding:1rem 1.75rem 1.5rem;">
                <button id="potrdi-ne" style="padding:.6rem 1.25rem;border-radius:8px;border:1.5px solid #B8C4D0;
                    background:transparent;color:#2E3F56;font-family:inherit;font-size:.9rem;
                    font-weight:500;cursor:pointer;">Prekliči</button>
                <button id="potrdi-da" style="padding:.6rem 1.25rem;border-radius:8px;border:none;
                    background:#C0392B;color:#fff;font-family:inherit;font-size:.9rem;
                    font-weight:500;cursor:pointer;">Izbriši</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    overlay.querySelector('#potrdi-da').onclick = () => { overlay.remove(); callback(true); };
    overlay.querySelector('#potrdi-ne').onclick = () => { overlay.remove(); callback(false); };
    overlay.onclick = (e) => { if (e.target === overlay) { overlay.remove(); callback(false); } };
}

// ── AJAX brisanje cenitve ──
function brisiCenitev(id, vrstica) {
    potrdiAkcijo('Ta cenitev bo trajno izbrisana. Tega dejanja ni mogoče razveljaviti.', async (potrjeno) => {
        if (!potrjeno) return;

        try {
            const res  = await fetch('api/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(id)}`
            });
            const data = await res.json();

            if (data.ok) {
                vrstica.style.transition = 'opacity .3s, transform .3s';
                vrstica.style.opacity    = '0';
                vrstica.style.transform  = 'translateX(20px)';
                setTimeout(() => {
                    vrstica.remove();
                    posodobiStevec(-1);
                    const tbody = document.querySelector('#tabela-cenitev tbody');
                    if (tbody && !tbody.querySelector('tr')) {
                        tbody.innerHTML = `
                            <tr><td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon">📋</div>
                                    <h3>Ni še nobene cenitve</h3>
                                    <p>Dodajte svojo prvo cenitev z gumbom zgoraj.</p>
                                </div>
                            </td></tr>`;
                    }
                }, 320);
                Toast.show('Cenitev je bila uspešno izbrisana.', 'success');
            } else {
                Toast.show(data.error ?? 'Napaka pri brisanju.', 'error');
            }
        } catch {
            Toast.show('Napaka pri komunikaciji s strežnikom.', 'error');
        }
    });
}

// ── Posodobi prikazano število cenitev ──
function posodobiStevec(delta) {
    const el = document.getElementById('stevec-cenitev');
    if (el) el.textContent = Math.max(0, (parseInt(el.textContent) || 0) + delta);
}

// ── Prednapolnjevanje urejanja ──
function urediCenitev(data) {
    const form = document.getElementById('form-uredi');
    if (!form) return;

    form.querySelector('[name="id"]').value                = data.id;
    form.querySelector('[name="naziv_narocnika"]').value   = data.naziv_narocnika;
    form.querySelector('[name="naslov_narocnika"]').value  = data.naslov_narocnika;
    form.querySelector('[name="namen_cenitve"]').value     = data.namen_cenitve;
    form.querySelector('[name="podlaga_vrednosti"]').value = data.podlaga_vrednosti;
    form.querySelector('[name="premisa_vrednosti"]').value = data.premisa_vrednosti;
    form.querySelector('[name="prvi_ogled"]').value        = data.prvi_ogled?.replace(' ', 'T').slice(0, 16);

    Modal.open('modal-uredi');
}

// ── Validacija obrazcev ──
function validirajObrazec(form) {
    let veljaven = true;
    form.querySelectorAll('[required]').forEach(el => {
        const err = el.closest('.form-group')?.querySelector('.form-error');
        if (!el.value.trim()) {
            el.classList.add('is-invalid');
            if (err) err.textContent = 'To polje je obvezno.';
            veljaven = false;
        } else {
            el.classList.remove('is-invalid');
            if (err) err.textContent = '';
        }
    });
    return veljaven;
}

// Sproti briši validacijske napake
document.addEventListener('input', e => {
    if (e.target.classList.contains('is-invalid')) {
        e.target.classList.remove('is-invalid');
        const err = e.target.closest('.form-group')?.querySelector('.form-error');
        if (err) err.textContent = '';
    }
});

// ── Inicializacija ──
    document.addEventListener('DOMContentLoaded', () => {
        Toast.init();

        document.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => Modal.close(btn.dataset.modalClose));
        });

        document.querySelectorAll('[data-modal-open]').forEach(btn => {
            btn.addEventListener('click', () => Modal.open(btn.dataset.modalOpen));
        });

        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', e => {
                if (!validirajObrazec(form)) e.preventDefault();
            });
        });
    });