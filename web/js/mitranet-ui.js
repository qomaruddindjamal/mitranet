/**
 * mitranet-ui.js - Global UI & CRUD Helper for MitraNet Rinjani WebUI
 * Standarisasi Alert, Confirm, Toast, dan AJAX Handler di seluruh halaman.
 */

window.MitraNet = window.MitraNet || {};

(function($) {
    'use strict';

    // 1. Toast Notification (Kompak, Elegan di Pojok Kanan Atas, Auto-Hilang)
    MitraNet.toast = function(icon, title, timer) {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 not loaded, fallback to console');
            return;
        }
        // Support signature: MitraNet.toast('Pesan', 'success') or MitraNet.toast('success', 'Pesan')
        let toastIcon = 'success';
        let toastTitle = title || '';
        if (['success', 'error', 'warning', 'info', 'question'].indexOf(icon) !== -1) {
            toastIcon = icon;
        } else if (['success', 'error', 'warning', 'info', 'question'].indexOf(title) !== -1) {
            toastIcon = title;
            toastTitle = icon;
        } else if (!toastTitle) {
            toastTitle = icon;
        }

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer || 3000,
            timerProgressBar: true,
            customClass: {
                popup: 'mitranet-compact-toast'
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: toastIcon,
            title: toastTitle
        });
    };

    // Auto Toast Runner: Secara otomatis mengubah alert banner sukses/gagal di WebUI menjadi toast kanan atas yang auto hilang
    $(document).ready(function() {
        // Cek alert-dismissible atau alert banner yang baru muncul dari submit POST
        $('.alert.alert-success, .alert.alert-danger, .alert.alert-info').each(function() {
            var $alert = $(this);
            // Lewati alert instruksi statis atau info panduan form yang bukan flash status
            if ($alert.hasClass('alert-static') || $alert.parents('#form-instructions').length > 0) {
                return;
            }
            // Khusus alert flash pesan sistem (seperti di dhcp_settings.php, system.php, dll)
            if ($alert.parent().hasClass('mitranet-window') || $alert.parent().hasClass('mitranet-page-container') || $alert.hasClass('alert-dismissible')) {
                var text = $alert.clone().children('.close, button').remove().end().text().trim();
                var isErr = $alert.hasClass('alert-danger');
                var isSuccess = $alert.hasClass('alert-success');
                var type = isErr ? 'error' : (isSuccess ? 'success' : 'info');

                if (text && text.length > 0 && text.length < 250) {
                    // Sembunyikan alert balok besar dan gantikan dengan toast kompak pojok kanan atas
                    $alert.hide();
                    setTimeout(function() {
                        MitraNet.toast(type, text, 3500);
                    }, 150);
                }
            }
        });
    });

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

    // 6. SweetAlert Khusus REBOOT SISTEM (dengan countdown & redirect ke /login.php)
    MitraNet.rebootSystem = function() {
        if (typeof Swal === 'undefined') {
            if (confirm('Apakah Anda yakin ingin me-restart perangkat MitraNet?')) {
                $.post('/system/reboot.php', { confirm_reboot: '1', ajax: 1 }, function() {
                    window.location.href = '/login.php';
                });
            }
            return;
        }

        Swal.fire({
            title: 'Restart Sistem?',
            html: `
                <div style="text-align: left; font-size: 13px; color: #4b5563; line-height: 1.6;">
                    <p style="margin-bottom: 8px;">Apakah Anda yakin ingin me-restart perangkat <b>MitraNet</b>?</p>
                    <ul style="padding-left: 20px; margin-bottom: 0;">
                        <li>Semua koneksi aktif akan terputus sementara.</li>
                        <li>Proses reboot memerlukan waktu sekitar 1–2 menit.</li>
                        <li>Setelah selesai, Anda akan diarahkan ke halaman login.</li>
                    </ul>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-rotate-right"></i> Ya, Restart Sekarang',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return $.ajax({
                    url: '/system/reboot.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { confirm_reboot: '1', ajax: 1 }
                }).then(function(res) {
                    if (!res || res.success === false) {
                        throw new Error(res && res.error ? res.error : 'Gagal mengirim sinyal restart.');
                    }
                    return res;
                }).catch(function(err) {
                    Swal.showValidationMessage(err.message || 'Gagal berkomunikasi dengan server.');
                });
            },
            allowOutsideClick: false
        }).then(function(result) {
            if (result.isConfirmed) {
                let timerInterval;
                let countdownSec = 45;

                Swal.fire({
                    title: 'Perangkat Sedang Restart...',
                    html: `
                        <div style="padding: 10px 0; text-align: center;">
                            <i class="fa-solid fa-spinner fa-spin fa-2x text-warning" style="margin-bottom: 15px;"></i>
                            <p style="font-size: 14px; color: #374151; margin-bottom: 5px;">Menunggu perangkat online kembali...</p>
                            <p style="font-size: 20px; font-weight: bold; color: #d97706;" id="swal-countdown">${countdownSec}s</p>
                            <small class="text-muted">Akan dialihkan otomatis ke halaman login.</small>
                        </div>
                    `,
                    timer: countdownSec * 1000,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function() {
                        const b = document.getElementById('swal-countdown');
                        timerInterval = setInterval(function() {
                            countdownSec--;
                            if (b) b.textContent = countdownSec + 's';
                            if (countdownSec <= 0) {
                                clearInterval(timerInterval);
                            }
                        }, 1000);
                    },
                    willClose: function() {
                        clearInterval(timerInterval);
                    }
                }).then(function() {
                    window.location.href = '/login.php';
                });
            }
        });
    };

    // 7. SweetAlert Khusus SHUTDOWN SISTEM (redirect ke /login.php)
    MitraNet.shutdownSystem = function() {
        if (typeof Swal === 'undefined') {
            if (confirm('Apakah Anda yakin ingin mematikan perangkat MitraNet?')) {
                $.post('/system/halt.php', { confirm_halt: '1', ajax: 1 }, function() {
                    window.location.href = '/login.php';
                });
            }
            return;
        }

        Swal.fire({
            title: 'Shutdown Sistem?',
            html: `
                <div style="text-align: left; font-size: 13px; color: #4b5563; line-height: 1.6;">
                    <p style="margin-bottom: 8px;">Apakah Anda yakin ingin <b>mematikan</b> perangkat MitraNet sepenuhnya?</p>
                    <ul style="padding-left: 20px; margin-bottom: 0;">
                        <li>Semua antarmuka dan layanan jaringan akan berhenti.</li>
                        <li>Perangkat harus dinyalakan kembali secara manual menggunakan tombol power fisik.</li>
                    </ul>
                </div>
            `,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-power-off"></i> Ya, Matikan Sekarang',
            cancelButtonText: 'Batal',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return $.ajax({
                    url: '/system/halt.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { confirm_halt: '1', ajax: 1 }
                }).then(function(res) {
                    if (!res || res.success === false) {
                        throw new Error(res && res.error ? res.error : 'Gagal mengirim sinyal shutdown.');
                    }
                    return res;
                }).catch(function(err) {
                    Swal.showValidationMessage(err.message || 'Gagal berkomunikasi dengan server.');
                });
            },
            allowOutsideClick: false
        }).then(function(result) {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Perangkat Dimatikan',
                    text: 'Sinyal shutdown telah dikirim. Mengalihkan ke halaman login...',
                    icon: 'info',
                    timer: 3000,
                    showConfirmButton: false,
                    allowOutsideClick: false
                }).then(function() {
                    window.location.href = '/login.php';
                });
            }
        });
    };

})(jQuery);
