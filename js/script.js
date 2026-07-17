// js/script.js - Mejorado y optimizado para todo tu sistema
document.addEventListener("DOMContentLoaded", function () {

    // Efecto de escritura en el login
    const typing = document.querySelector(".typing");
    if (typing) {
        const text = typing.textContent;
        typing.textContent = "";
        let i = 0;
        const timer = setInterval(() => {
            typing.textContent += text.charAt(i);
            i++;
            if (i > text.length) clearInterval(timer);
        }, 90);
    }

    // Validación del formulario de login con SweetAlert2
    const formLogin = document.getElementById("formLogin");
    if (formLogin) {
        formLogin.addEventListener("submit", function(e) {
            const usuario = formLogin.querySelector("input[name='usuario']").value.trim();
            const password = formLogin.querySelector("input[name='password']").value;

            if (!usuario || !password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor completa usuario y contraseña',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        });
    }

    // Confirmación para cerrar sesión
    document.querySelectorAll("a[href='logout.php'], #cerrar-sesion").forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            Swal.fire({
                title: "¿Cerrar sesión?",
                text: "Estás a punto de salir del sistema",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Sí, salir",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.href;
                }
            });
        });
    });

    // Confirmación para eliminar usuario
    document.querySelectorAll("a[href*='eliminar=']").forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            const nombre = this.closest("tr")?.querySelector("td:nth-child(2)")?.textContent || "este usuario";
            Swal.fire({
                title: "¿Eliminar usuario?",
                text: `¿Estás seguro de eliminar a ${nombre}? Esta acción no se puede deshacer.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.href;
                }
            });
        });
    });

    // Tooltip de Bootstrap
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

});