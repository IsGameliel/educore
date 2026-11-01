/* Shared dashboard calendar. Date-only values stay in the institution's calendar day. */
(() => {
  'use strict';
  document.querySelectorAll('[data-dashboard-calendar]').forEach((calendar) => {
    const parse = (value) => {
      const [year, month, day] = value.split('-').map(Number);
      return new Date(year, month - 1, day, 12);
    };
    const key = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const today = parse(calendar.dataset.today);
    let selected = new Date(today);
    let month = new Date(today.getFullYear(), today.getMonth(), 1, 12);
    const events = JSON.parse(calendar.querySelector('[data-calendar-events]').textContent);
    const days = calendar.querySelector('[data-calendar-days]');
    const locale = document.documentElement.lang || 'en';
    const longDate = (date) => date.toLocaleDateString(locale, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

    function render(focus = false) {
      calendar.querySelector('[data-calendar-month]').textContent = month.toLocaleDateString(locale, { month: 'long', year: 'numeric' });
      days.replaceChildren();
      const start = new Date(month.getFullYear(), month.getMonth(), 1 - month.getDay(), 12);
      for (let i = 0; i < 42; i++) {
        const date = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i, 12);
        const value = key(date);
        const due = events.filter((event) => event.date === value);
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.date = value;
        button.textContent = date.getDate();
        button.className = 'calendar-day';
        button.classList.toggle('is-outside', date.getMonth() !== month.getMonth());
        button.classList.toggle('is-today', value === key(today));
        button.classList.toggle('has-events', due.length > 0);
        button.setAttribute('aria-pressed', String(value === key(selected)));
        button.setAttribute('aria-label', `${longDate(date)}${value === key(today) ? ', today' : ''}${due.length ? `, ${due.length} project(s) due` : ''}`);
        if (value === key(today)) button.setAttribute('aria-current', 'date');
        button.tabIndex = value === key(selected) ? 0 : -1;
        days.appendChild(button);
      }
      calendar.querySelector('[data-calendar-selected]').textContent = longDate(selected);
      const agenda = calendar.querySelector('[data-calendar-agenda]');
      agenda.replaceChildren();
      const due = events.filter((event) => event.date === key(selected));
      (due.length ? due.map((event) => event.title) : ['No project deadlines for this day.']).forEach((title) => {
        const item = document.createElement('li');
        item.textContent = title;
        agenda.appendChild(item);
      });
      if (focus) days.querySelector(`[data-date="${key(selected)}"]`).focus();
    }
    function select(date, focus = false) {
      selected = date;
      month = new Date(date.getFullYear(), date.getMonth(), 1, 12);
      render(focus);
    }
    function moveMonth(offset) {
      const target = new Date(month.getFullYear(), month.getMonth() + offset, 1, 12);
      const lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0, 12).getDate();
      select(new Date(target.getFullYear(), target.getMonth(), Math.min(selected.getDate(), lastDay), 12));
    }
    calendar.querySelector('[data-calendar-prev]').addEventListener('click', () => moveMonth(-1));
    calendar.querySelector('[data-calendar-next]').addEventListener('click', () => moveMonth(1));
    calendar.querySelector('[data-calendar-today]').addEventListener('click', () => select(new Date(today)));
    days.addEventListener('click', (event) => {
      const button = event.target.closest('[data-date]');
      if (button) select(parse(button.dataset.date), true);
    });
    days.addEventListener('keydown', (event) => {
      const offsets = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
      if (!Object.prototype.hasOwnProperty.call(offsets, event.key)) return;
      event.preventDefault();
      select(new Date(selected.getFullYear(), selected.getMonth(), selected.getDate() + offsets[event.key], 12), true);
    });
    render();
  });
})();
