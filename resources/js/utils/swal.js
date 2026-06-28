import Swal from 'sweetalert2';

export const swalClass = {
    popup: 'ts-swal-popup',
    title: 'ts-swal-title',
    htmlContainer: 'ts-swal-text',
    confirmButton: 'ts-swal-btn ts-swal-btn--confirm',
    cancelButton: 'ts-swal-btn ts-swal-btn--cancel',
    actions: 'ts-swal-actions',
    timerProgressBar: 'ts-swal-timer',
};

const ThemeSwal = Swal.mixin({
    background: '#0f1e3d',
    color: '#e2e8f0',
    buttonsStyling: false,
    customClass: swalClass,
});

export default ThemeSwal;
