document.addEventListener('DOMContentLoaded', () => {
    /* Hiện/ẩn khối mã QR chuyển khoản theo phương thức thanh toán đang chọn */
    const payRadios = document.querySelectorAll('input[name="payment_method"]');
    const qrPanel = document.querySelector('.bank-qr-panel');
    if (payRadios.length && qrPanel) {
        const sync = () => {
            const checked = document.querySelector('input[name="payment_method"]:checked');
            qrPanel.style.display = (checked && checked.value === qrPanel.dataset.showFor) ? '' : 'none';
        };
        payRadios.forEach(r => r.addEventListener('change', sync));
        sync();
    }

    /* Xác nhận trước khi thực hiện hành động (xóa, hủy...) - áp dụng cho cả <a> và <form> (nút submit) */
    document.querySelectorAll('a[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    /* Ngày trả phòng luôn sau ngày nhận phòng */
    document.querySelectorAll('[name="check_in"]').forEach(checkin => {
        const form = checkin.closest('form');
        const checkout = form ? form.querySelector('[name="check_out"]') : null;
        if (!checkout) return;
        const sync = () => {
            if (checkin.value) {
                checkout.min = checkin.value;
                if (checkout.value && checkout.value <= checkin.value) {
                    const d = new Date(checkin.value);
                    d.setDate(d.getDate() + 1);
                    checkout.value = d.toISOString().slice(0, 10);
                }
            }
        };
        checkin.addEventListener('change', sync);
    });

    /* Menu mobile */
    const navToggle = document.querySelector('.nav-toggle');
    const mainNav = document.getElementById('main-nav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', () => {
            const open = mainNav.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* Nút yêu thích (lưu tạm trong bộ nhớ trình duyệt của phiên hiện tại) */
    document.querySelectorAll('[data-heart]').forEach(btn => {
        btn.addEventListener('click', () => {
            const pressed = btn.getAttribute('aria-pressed') === 'true';
            btn.setAttribute('aria-pressed', pressed ? 'false' : 'true');
        });
    });

    /* Đổi ảnh lớn trong trang chi tiết phòng */
    const galleryMain = document.getElementById('gallery-main');
    document.querySelectorAll('[data-gallery]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (galleryMain) galleryMain.src = btn.dataset.gallery;
            document.querySelectorAll('[data-gallery]').forEach(b => b.setAttribute('aria-current', 'false'));
            btn.setAttribute('aria-current', 'true');
        });
    });

    /* Thanh trượt chọn khoảng giá (2 đầu kéo) */
    document.querySelectorAll('[data-range]').forEach(wrap => {
        const [minInput, maxInput] = wrap.querySelectorAll('input[type="range"]');
        const fill = wrap.querySelector('.range-fill');
        const labelMin = document.querySelector('[data-range-min]');
        const labelMax = document.querySelector('[data-range-max]');
        if (!minInput || !maxInput) return;
        const lo = parseFloat(minInput.min), hi = parseFloat(minInput.max);
        const fmt = v => Number(v).toLocaleString('vi-VN') + 'đ';
        const paint = () => {
            let a = parseFloat(minInput.value), b = parseFloat(maxInput.value);
            if (a > b) { [a, b] = [b, a]; }
            const pa = ((a - lo) / (hi - lo)) * 100;
            const pb = ((b - lo) / (hi - lo)) * 100;
            if (fill) { fill.style.left = pa + '%'; fill.style.right = (100 - pb) + '%'; }
            if (labelMin) labelMin.textContent = fmt(a);
            if (labelMax) labelMax.textContent = fmt(b);
        };
        minInput.addEventListener('input', paint);
        maxInput.addEventListener('input', paint);
        paint();
    });

    /* Bộ lọc phòng tự động gửi khi đổi lựa chọn */
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.querySelectorAll('input[type="checkbox"], select').forEach(el => {
            el.addEventListener('change', () => filterForm.requestSubmit());
        });
        filterForm.querySelectorAll('input[type="range"]').forEach(el => {
            el.addEventListener('change', () => filterForm.requestSubmit());
        });
    }

    /* Tăng/giảm số lượng dịch vụ thêm + tính lại tổng tiền tạm tính */
    const summaryTotal = document.querySelector('[data-summary-total]');
    const summaryText = document.querySelector('[data-summary-text]');
    const priceBase = parseFloat(document.getElementById('booking-form')?.dataset.price || 0);
    const recalc = () => {
        if (!summaryTotal) return;
        let total = priceBase;
        document.querySelectorAll('[data-qty] input').forEach(inp => {
            total += (parseFloat(inp.dataset.price) || 0) * (parseInt(inp.value) || 0);
        });
        summaryTotal.textContent = Math.round(total).toLocaleString('vi-VN') + 'đ';
        if (summaryText) {
            const hasPromo = document.getElementById('b-promo')?.value.trim();
            summaryText.textContent = hasPromo ? 'Tạm tính (chưa gồm khuyến mãi)' : 'Tạm tính';
        }
    };
    document.querySelectorAll('[data-qty]').forEach(box => {
        const input = box.querySelector('input');
        box.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                const step = parseInt(btn.dataset.step);
                let val = (parseInt(input.value) || 0) + step;
                val = Math.max(parseInt(input.min) || 0, Math.min(parseInt(input.max) || 99, val));
                input.value = val;
                recalc();
    document.getElementById('b-promo')?.addEventListener('input', recalc);
            });
        });
        input.addEventListener('input', recalc);
    });
    recalc();

    /* Đổi loại phòng trong form đặt phòng -> chuyển sang phòng tương ứng, giữ ngày/khách đã chọn */
    document.querySelectorAll('[data-room-switch]').forEach(select => {
        select.addEventListener('change', () => {
            const form = select.closest('form');
            const params = new URLSearchParams();
            params.set('room_id', select.value);
            ['check_in', 'check_out', 'guests'].forEach(name => {
                const el = form?.querySelector(`[name="${name}"]`);
                if (el && el.value) params.set(name, el.value);
            });
            window.location.href = select.dataset.roomSwitch + '?' + params.toString();
        });
    });

    /* Hộp thoại chi tiết đơn (trang lịch sử đặt phòng) */
    document.querySelectorAll('[data-open-dialog]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById(btn.dataset.openDialog)?.showModal();
        });
    });
    document.querySelectorAll('[data-close-dialog]').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('dialog')?.close());
    });
    document.querySelectorAll('dialog').forEach(dlg => {
        dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    });

    /* Lightbox thư viện ảnh */
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        const lightboxImg = lightbox.querySelector('img');
        document.querySelectorAll('[data-lightbox]').forEach(fig => {
            fig.addEventListener('click', () => {
                lightboxImg.src = fig.dataset.lightbox;
                lightbox.showModal();
            });
        });
    }

    /* Bộ lọc chip (thư viện ảnh) */
    document.querySelectorAll('[data-chip-group]').forEach(group => {
        const chips = group.querySelectorAll('.chip-btn');
        const targetSel = group.dataset.chipGroup;
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => c.setAttribute('aria-pressed', 'false'));
                chip.setAttribute('aria-pressed', 'true');
                const cat = chip.dataset.chip;
                document.querySelectorAll(targetSel).forEach(item => {
                    item.style.display = (cat === 'all' || item.dataset.cat === cat) ? '' : 'none';
                });
            });
        });
    });

    /* Kiểm tra dữ liệu phía trình duyệt cho form có thuộc tính data-validate.
       Quy tắc đặt ở từng ô nhập qua data-rule, ví dụ: data-rule="required email" hoặc "required minlen:6".
       Các quy tắc này khớp với kiểm tra ở server (PHP) - server vẫn là lớp kiểm tra bắt buộc cuối cùng. */
    const RULE_MESSAGES = {
        required: 'Vui lòng nhập thông tin này.',
        email: 'Email không đúng định dạng (ví dụ: ten@gmail.com).',
        phone: 'Số điện thoại không hợp lệ (8-15 ký tự, chỉ gồm số, dấu + - và khoảng trắng).',
        maxlen: n => 'Không được vượt quá ' + n + ' ký tự.',
        minlen: n => 'Cần tối thiểu ' + n + ' ký tự.'
    };

    function checkField(input) {
        const value = input.value.trim();
        const rules = (input.dataset.rule || '').split(/\s+/).filter(Boolean);
        for (const rule of rules) {
            const [name, arg] = rule.split(':');
            if (name === 'required' && value === '') return RULE_MESSAGES.required;
            if (value === '') continue; // các quy tắc khác chỉ áp dụng khi đã nhập
            if (name === 'email' && !(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) && value.length <= 150)) return RULE_MESSAGES.email;
            if (name === 'phone' && !/^[0-9+\-\s]{8,15}$/.test(value)) return RULE_MESSAGES.phone;
            if (name === 'minlen' && input.value.length < parseInt(arg, 10)) return RULE_MESSAGES.minlen(arg);
            if (name === 'maxlen' && value.length > parseInt(arg, 10)) return RULE_MESSAGES.maxlen(arg);
        }
        return '';
    }

    function showFieldError(input, message) {
        const group = input.closest('.form-group') || input.parentNode;
        let box = group.querySelector('.field-error');
        if (!message) {
            input.classList.remove('invalid');
            input.removeAttribute('aria-invalid');
            if (box) box.remove();
            return;
        }
        input.classList.add('invalid');
        input.setAttribute('aria-invalid', 'true');
        if (!box) {
            box = document.createElement('small');
            box.className = 'field-error';
            box.setAttribute('role', 'alert');
            group.appendChild(box);
        }
        box.textContent = message;
    }

    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.setAttribute('novalidate', ''); // dùng thông báo tiếng Việt của mình thay cho bong bóng mặc định của trình duyệt
        const fields = Array.from(form.querySelectorAll('[data-rule]'));
        fields.forEach(input => {
            input.addEventListener('blur', () => showFieldError(input, checkField(input)));
            input.addEventListener('input', () => {
                if (input.classList.contains('invalid')) showFieldError(input, checkField(input));
            });
        });
        form.addEventListener('submit', e => {
            let firstBad = null;
            fields.forEach(input => {
                const msg = checkField(input);
                showFieldError(input, msg);
                if (msg && !firstBad) firstBad = input;
            });
            if (firstBad) {
                e.preventDefault();
                firstBad.focus();
            }
        });
    });
});
