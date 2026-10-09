/**
 * mitranet-ui.js - Global UI & CRUD Helper for MitraNet Rinjani WebUI
 * Standarisasi Alert, Confirm, Toast, dan AJAX Handler di seluruh halaman.
 */

window.MitraNet = window.MitraNet || {};

(function($) {
    'use strict';

    // 1. Toast Notification (Pojok kanan atas)
    MitraNet.toast = function(icon, title, timer) {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 not loaded, fallback to console');
            return;
        }
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer || 2500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({
            icon: icon || 'success',
            title: title || 'Operasi berhasil'
        });
    };

    // 2. Alert Standar
    MitraNet.alert = function(title, text, icon) {
        if (typeof Swal === 'undefined') {
            alert(text || title);
            return Promise.resolve();
        }
        return Swal.fire({
            title: title,
            text: text,
            icon: icon || 'info',
            confirmButtonColor: '#00bcd4'
        });
    };

    // 3. Konfirmasi Delete / Hapus Global
    MitraNet.confirmDelete = function(options) {
        /**
         * options: {
         *   title: 'Hapus Interface?',
         *   name: 'veth0',
         *   warning: 'Tindakan ini langsung diterapkan ke sistem Linux.',
         *   url: 'interfaces.php',
         *   data: { action: 'delete_interface', interface: 'veth0' },
         *   onSuccess: function(res) { ... }
         * }
         */
        if (typeof Swal === 'undefined') {
            if (confirm('Yakin ingin menghapus ' + (options.name || '') + '?')) {
                $.post(options.url, Object.assign({ ajax: 1 }, options.data || {}), function(res) {
                    location.reload();
                });
            }
            return;
        }

        var resName = options.name || '';
        var warningText = options.warning || 'Tindakan ini langsung diterapkan ke kernel Linux.';

        Swal.fire({
            title: options.title || 'Konfirmasi Hapus',
            html: 'Apakah Anda yakin ingin menghapus <b>' + resName + '</b>?<br><small class="text-danger">' + warningText + '</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e53935',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-trash"></i> Hapus',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return $.ajax({
                    url: options.url,
                    type: 'POST',
                    dataType: 'json',
                    data: Object.assign({ ajax: 1 }, options.data || {})
                }).then(function(res) {
                    if (!res || res.success === false) {
                        throw new Error(res && res.error ? res.error : 'Gagal menghapus data.');
                    }
                    return res;
                }).catch(function(err) {
                    Swal.showValidationMessage(err.message || 'Gagal berkomunikasi dengan server.');
                });
            },
            allowOutsideClick: function() { return !Swal.isLoading(); }
        }).then(function(result) {
            if (result.isConfirmed) {
                MitraNet.toast('success', "Berhasil menghapus '" + resName + "'");
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(result.value);
                } else {
                    // Hapus baris tabel yang bersangkutan jika ada
                    $('tr[data-ifname="' + resName + '"], tr[data-name="' + resName + '"]').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            }
        });
    };

    // 4. Prompt Input Teks Cepat (Misal: Comment / Rename)
    MitraNet.promptInput = function(options) {
        /**
         * options: {
         *   title: 'Ubah Comment',
         *   value: 'Komentar awal',
         *   placeholder: 'Tulis komentar...',
         *   url: 'interfaces.php',
         *   data: { action: 'save_comment', ... },
         *   onSuccess: function(res) { ... }
         * }
         */
        if (typeof Swal === 'undefined') {
            var val = prompt(options.title, options.value || '');
            if (val !== null) {
                $.post(options.url, Object.assign({ ajax: 1, value: val }, options.data || {}), function() {
                    location.reload();
                });
            }
            return;
        }

        Swal.fire({
            title: options.title || 'Input Data',
            input: options.inputType || 'text',
            inputValue: options.value || '',
            inputPlaceholder: options.placeholder || '',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-check"></i> Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#00bcd4',
            showLoaderOnConfirm: true,
            preConfirm: function(val) {
                var postData = Object.assign({ ajax: 1 }, options.data || {});
                if (options.inputKey) {
                    postData[options.inputKey] = val;
                } else {
                    postData.value = val;
                }
                return $.ajax({
                    url: options.url,
                    type: 'POST',
                    dataType: 'json',
                    data: postData
                }).then(function(res) {
                    if (!res || res.success === false) {
                        throw new Error(res && res.error ? res.error : 'Gagal menyimpan perubahan.');
                    }
                    return res;
                }).catch(function(err) {
                    Swal.showValidationMessage(err.message || 'Gagal menyimpan perubahan.');
                });
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                MitraNet.toast('success', options.successMsg || 'Data berhasil disimpan');
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(result.value);
                }
            }
        });
    };

    // 5. Konfirmasi Eksekusi Perintah Cepat (misal: Reboot, Reset, Toggle Service)
    MitraNet.confirmAction = function(options) {
        if (typeof Swal === 'undefined') {
            if (confirm(options.title || 'Lanjutkan aksi?')) {
                $.post(options.url, Object.assign({ ajax: 1 }, options.data || {}), function() {
                    location.reload();
                });
            }
            return;
        }

        Swal.fire({
            title: options.title || 'Konfirmasi Tindakan',
            html: options.html || 'Apakah Anda ingin melanjutkan tindakan ini?',
            icon: options.icon || 'question',
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '#00bcd4',
            cancelButtonColor: '#6c757d',
            confirmButtonText: options.confirmText || 'Ya, Lanjutkan',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return $.ajax({
                    url: options.url,
                    type: 'POST',
                    dataType: 'json',
                    data: Object.assign({ ajax: 1 }, options.data || {})
                }).then(function(res) {
                    if (!res || res.success === false) {
                        throw new Error(res && res.error ? res.error : 'Tindakan gagal.');
                    }
                    return res;
                }).catch(function(err) {
                    Swal.showValidationMessage(err.message || 'Gagal berkomunikasi dengan server.');
                });
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                MitraNet.toast('success', options.successMsg || 'Tindakan berhasil dieksekusi.');
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(result.value);
                }
            }
        });
    };

})(jQuery);
