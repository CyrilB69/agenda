document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const submitter = event.submitter;
      const message = submitter?.getAttribute('data-confirm') || form.getAttribute('data-confirm');
      if (message && !window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('input[type="password"]').forEach((input) => {
    const parent = input.parentNode;
    if (!parent || input.closest('.password-field')) return;

    const wrapper = document.createElement('div');
    wrapper.className = 'password-field';
    parent.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'password-toggle';
    toggle.textContent = 'Afficher';
    toggle.setAttribute('aria-label', 'Afficher le mot de passe');
    wrapper.appendChild(toggle);

    toggle.addEventListener('click', () => {
      const isVisible = input.type === 'text';
      input.type = isVisible ? 'password' : 'text';
      toggle.textContent = isVisible ? 'Afficher' : 'Masquer';
      toggle.setAttribute('aria-label', isVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    });
  });

  const formatLocalDateTime = (date) => {
    const pad = (value) => String(value).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  };

  document.querySelectorAll('.reservation-form').forEach((form) => {
    const start = form.querySelector('input[name="starts_at"]');
    const end = form.querySelector('input[name="ends_at"]');
    if (!start || !end) return;

    start.addEventListener('change', () => {
      if (!start.value) return;
      const startDate = new Date(start.value);
      const endDate = new Date(end.value);
      if (!end.value || endDate <= startDate) {
        startDate.setHours(startDate.getHours() + 1);
        end.value = formatLocalDateTime(startDate);
      }
    });
  });

  document.querySelectorAll('[data-export-rooms]').forEach((fieldset) => {
    const allRooms = fieldset.querySelector('[data-export-all]');
    const roomInputs = Array.from(fieldset.querySelectorAll('[data-export-room]'));
    if (!allRooms || roomInputs.length === 0) return;

    const syncRooms = () => {
      roomInputs.forEach((input) => {
        input.disabled = allRooms.checked;
        if (allRooms.checked) {
          input.checked = false;
        }
      });
    };

    allRooms.addEventListener('change', syncRooms);
    roomInputs.forEach((input) => {
      input.addEventListener('change', () => {
        if (input.checked) {
          allRooms.checked = false;
          syncRooms();
        }
      });
    });

    syncRooms();
  });
});
