function showForm(formId) {
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
    document.getElementById(formId).classList.add("active");
}

document.querySelectorAll('select').forEach(select => {
  select.addEventListener('change', function() {
    if(this.value) {
      this.classList.add('filled');
    } else {
      this.classList.remove('filled');
    }
  });
});

/* Dashboard */

function toggleProfileMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('profileMenu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    closeAllActionMenus();
}
        
function toggleMenu(e, id) {
    e.stopPropagation();
    const menu = document.getElementById('menu-' + id);
    const isAlreadyOpen = menu.style.display === 'block';
    closeAllActionMenus();
    document.getElementById('profileMenu').style.display = 'none';
    if (!isAlreadyOpen) {
        menu.style.display = 'block';
    }
}

function closeAllActionMenus() {
    document.querySelectorAll('.action-menu').forEach(el => el.style.display = 'none');
}

window.onclick = function(e) {
    closeAllActionMenus();
    const profileMenu = document.getElementById('profileMenu');
    if (profileMenu) profileMenu.style.display = 'none';
}

/* Modali */

function closeModals() {
    document.getElementById('modalRole').style.display = 'none';
    document.getElementById('modalSalary').style.display = 'none';
    document.getElementById('modalDelete').style.display = 'none';
}

function openRoleModal(id, name, currentRole) {
    closeAllActionMenus();
    document.getElementById('roleModalName').innerText = name;
    document.getElementById('roleEditId').value = id;
    document.getElementById('newRoleSelect').value = currentRole;
    document.getElementById('modalRole').style.display = 'flex';
}

function openSalaryModal(id, name, currentSalary) {
    closeAllActionMenus();
    document.getElementById('salaryModalName').innerText = name;
    document.getElementById('salaryEditId').value = id;
    document.getElementById('newSalaryInput').value = currentSalary;
    document.getElementById('modalSalary').style.display = 'flex';
}

function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const rows = document.querySelectorAll('.data-row');
    rows.forEach(row => {
        const textContent = row.querySelector('td:first-child').innerText.toLowerCase();
        const role = row.getAttribute('data-role');
        const matchesSearch = textContent.includes(searchInput);
        const matchesRole = (roleFilter === 'all' || role === roleFilter);
        row.style.display = (matchesSearch && matchesRole) ? '' : 'none';
    });
}

/* Ruolo */

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

async function submitRoleUpdate() {
    const id = document.getElementById('roleEditId').value;
    const newRole = document.getElementById('newRoleSelect').value;
    if (!id || !newRole) return;
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('ruolo', newRole);
        formData.append('action', 'update_ruolo');
        formData.append('csrf_token', getCsrfToken());
        const response = await fetch('actions.php', { method: 'POST', body: formData });
        const data = await response.json();
        closeModals();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500); 
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error(error); showToast("Errore di connessione", "error");
    }
}

/* Stipendio */

async function submitSalaryUpdate() {
    const id = document.getElementById('salaryEditId').value;
    const newSalary = document.getElementById('newSalaryInput').value;
    if (!id || !newSalary) return;
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('stipendio', newSalary);
        formData.append('action', 'update_stipendio');
        formData.append('csrf_token', getCsrfToken());
        const response = await fetch('actions.php', { method: 'POST', body: formData });
        const data = await response.json(); 
        closeModals();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error(error); showToast("Errore di connessione", "error");
    }
}

function openDeleteModal(id, name) {
    closeAllActionMenus();
    document.getElementById('deleteModalName').innerText = name;
    document.getElementById('deleteId').value = id;
    document.getElementById('modalDelete').style.display = 'flex';
}

/* Elimina utente */

async function submitDeleteUser() {
    const id = document.getElementById('deleteId').value;
    if (!id) return;
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', 'delete_utente');
        formData.append('csrf_token', getCsrfToken());
        const response = await fetch('actions.php', { method: 'POST', body: formData });
        const data = await response.json(); 
        closeModals();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error(error); showToast("Errore di connessione", "error");
    }
}

// Notifiche

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const msgSpan = document.getElementById('toastMessage');
    const iconDiv = document.getElementById('toastIcon');
    msgSpan.innerText = message;
    iconDiv.className = 'toast-icon ' + type;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}


document.addEventListener("DOMContentLoaded", function() {
    const bubble = document.getElementById('profileBubble');
    if (bubble) {
        bubble.addEventListener('click', toggleProfileMenu);
    }
});