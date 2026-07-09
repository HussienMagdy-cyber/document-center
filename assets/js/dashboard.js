// =========================
// DOCUMENT CENTER JS
// =========================

// TAB SWITCHING
document.addEventListener("click", function (e) {
  const tabBtn = e.target.closest(".doccenter-nav-item");
  if (!tabBtn) return;

  const tab = tabBtn.getAttribute("data-tab");
  if (!tab) return;

  document.querySelectorAll(".doccenter-section").forEach((sec) => {
    sec.classList.remove("doccenter-section-active");
  });

  const activeSec = document.querySelector(
    `.doccenter-section[data-tab="${tab}"]`
  );
  if (activeSec) activeSec.classList.add("doccenter-section-active");

  document.querySelectorAll(".doccenter-nav-item").forEach((btn) => {
    btn.classList.remove("doccenter-nav-item-active");
  });

  tabBtn.classList.add("doccenter-nav-item-active");
});

// =========================
// GROUP MODAL
// =========================

const groupOverlay = document.getElementById("doccenter-group-overlay");
const groupForm = document.getElementById("doccenter-group-form");
const groupIdInput = document.getElementById("doccenter-group-id");
const groupNameInput = document.getElementById("doccenter-group-name");
const groupUsersBox = document.getElementById("doccenter-group-users-box");
const groupCancelBtn = document.getElementById("doccenter-group-cancel");

function openGroupModal(id, name, viewers) {
  if (!groupOverlay) return;

  groupOverlay.classList.add("doccenter-user-overlay-active");
  groupIdInput.value = id;
  groupNameInput.value = name;

  const assigned = viewers ? viewers.split(",") : [];

  groupUsersBox.querySelectorAll("input[type='checkbox']").forEach((cb) => {
    cb.checked = assigned.includes(cb.value);
  });
}

function closeGroupModal() {
  if (groupOverlay) {
    groupOverlay.classList.remove("doccenter-user-overlay-active");
  }
}

document.querySelectorAll(".doccenter-group-edit-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    openGroupModal(
      btn.dataset.groupId,
      btn.dataset.groupName,
      btn.dataset.groupViewers
    );
  });
});

if (groupCancelBtn) {
  groupCancelBtn.addEventListener("click", function (e) {
    e.preventDefault();
    closeGroupModal();
  });
}

if (groupOverlay) {
  groupOverlay.addEventListener("click", function (e) {
    if (e.target === groupOverlay) closeGroupModal();
  });
}

// =========================
// FORCE FORM SUBMIT
// =========================
if (groupForm) {
  groupForm.addEventListener("submit", function () {
    // No preventDefault anywhere — allow normal POST
  });
}

// =========================
// MOBILE SIDEBAR
// =========================

const mobileToggle = document.getElementById("doccenter-mobile-toggle");
const sidebar = document.getElementById("dc-sidebar");

if (mobileToggle && sidebar) {
  mobileToggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");
  });
}

// =========================
// PROMOTE / DEMOTE (AJAX)
// =========================

document.addEventListener("click", function (e) {
  if (e.target.matches(".doccenter-promote-admin-btn")) {
    const id = e.target.dataset.userId;

    const form = document.createElement("form");
    form.method = "post";

    form.innerHTML = `
            <input type="hidden" name="doccenter_action" value="promote_to_admin">
            <input type="hidden" name="user_id" value="${id}">
        `;

    document.body.appendChild(form);
    form.submit();
  }

  if (e.target.matches(".doccenter-demote-admin-btn")) {
    const id = e.target.dataset.userId;

    const form = document.createElement("form");
    form.method = "post";

    form.innerHTML = `
            <input type="hidden" name="doccenter_action" value="demote_to_viewer">
            <input type="hidden" name="user_id" value="${id}">
        `;

    document.body.appendChild(form);
    form.submit();
  }
});
