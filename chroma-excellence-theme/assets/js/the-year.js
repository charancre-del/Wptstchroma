(function () {
  'use strict';

  const root = document.querySelector('.chroma-year-page');
  if (!root) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const revealItems = root.querySelectorAll('.reveal');

  if ('IntersectionObserver' in window && !reducedMotion) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('in');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
    revealItems.forEach((item) => observer.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add('in'));
  }

  const weekItems = Array.from(root.querySelectorAll('#yearList .wk'));
  if (!weekItems.length) return;

  const weeks = weekItems.map((item) => {
    const roomWrap = item.querySelector('.wkRooms');
    const rooms = roomWrap
      ? Array.from(roomWrap.querySelectorAll('i')).map((node) => ({
          room: node.getAttribute('data-room'),
          text: node.textContent.trim(),
        }))
      : null;
    const home = item.querySelector('.wkHome');
    const homeClone = home ? home.cloneNode(true) : null;
    if (homeClone && homeClone.querySelector('b')) homeClone.querySelector('b').remove();
    const nameNode = item.querySelector('.wkName');

    return {
      week: Number(item.getAttribute('data-w')),
      month: item.getAttribute('data-m') || '',
      theme: nameNode && nameNode.firstChild ? nameNode.firstChild.textContent.trim() : '',
      line: item.querySelector('.wkLine') ? item.querySelector('.wkLine').textContent.trim() : '',
      home: homeClone ? homeClone.textContent.trim() : '',
      rooms,
    };
  });

  const startDate = new Date(2026, 7, 3);
  const currentWeekNumber = () => {
    if (Date.now() < startDate.getTime()) {
      return 1;
    }

    const elapsedDays = Math.floor((Date.now() - startDate.getTime()) / 86400000);
    return ((Math.floor(elapsedDays / 7) % 52) + 52) % 52 + 1;
  };
  const weekDate = (number) => {
    const date = new Date(startDate.getTime() + (number - 1) * 7 * 86400000);
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric' });
  };

  const currentNumber = currentWeekNumber();
  const current = weeks[currentNumber - 1];
  if (current) {
    const values = {
      nowWeek: `Week ${current.week} · ${current.month}`,
      nowTheme: current.theme,
      nowLine: current.line,
      nowHome: current.home,
    };
    Object.entries(values).forEach(([id, value]) => {
      const element = root.querySelector(`#${id}`);
      if (element) element.textContent = value;
    });
  }

  const slider = root.querySelector('#weekSlider');
  const monthRow = root.querySelector('#monthRow');
  if (!slider || !monthRow) return;

  const months = weeks.reduce((items, week) => {
    if (!items.includes(week.month)) items.push(week.month);
    return items;
  }, []);
  monthRow.innerHTML = months.map((month) => {
    const count = weeks.filter((week) => week.month === month).length;
    return `<button type="button" class="mTick" data-month="${month}" style="flex:${count}">${month.slice(0, 3)}</button>`;
  }).join('');

  const tag = root.querySelector('#swTag');
  const meta = root.querySelector('#swMeta');
  const theme = root.querySelector('#swTheme');
  const line = root.querySelector('#swLine');
  const home = root.querySelector('#swHome');
  const roomLink = root.querySelector('#swRoomsLink');
  let lastWeek = -1;

  const paintWeek = (number) => {
    const data = weeks[number - 1];
    if (!data || number === lastWeek) return;
    lastWeek = number;
    theme?.classList.add('fade');
    line?.classList.add('fade');

    window.setTimeout(() => {
      if (tag) tag.textContent = `Week ${data.week}`;
      if (meta) meta.textContent = `Week of ${weekDate(data.week)} · ${data.month}`;
      if (theme) theme.textContent = data.theme;
      if (line) line.textContent = data.line;
      if (home) home.textContent = data.home;
      theme?.classList.remove('fade');
      line?.classList.remove('fade');
    }, reducedMotion ? 0 : 150);

    if (roomLink) {
      roomLink.hidden = !data.rooms;
      if (data.rooms) roomLink.setAttribute('data-w', String(data.week));
    }
    Array.from(monthRow.children).forEach((button) => {
      button.classList.toggle('on', button.getAttribute('data-month') === data.month);
    });
  };

  slider.addEventListener('input', () => {
    paintWeek(Number(slider.value));
    const hint = root.querySelector('#scrubHint');
    if (hint) hint.style.opacity = '0';
  });
  Array.from(monthRow.children).forEach((button) => {
    button.addEventListener('click', () => {
      const first = weeks.find((week) => week.month === button.getAttribute('data-month'));
      if (!first) return;
      slider.value = String(first.week);
      paintWeek(first.week);
    });
  });
  slider.value = String(currentNumber);
  paintWeek(currentNumber);

  const roomColors = {
    Infants: '#7D5BA6',
    Toddlers: '#4A6C7C',
    Preschool: '#4A7C59',
    'Pre-K Prep': '#C26524',
    'Pre-K': '#C2A024',
    Schoolagers: '#A84B38',
  };
  const roomAges = {
    Infants: '6 weeks–15 months',
    Toddlers: '12–24 months',
    Preschool: '2–3 years',
    'Pre-K Prep': '3–4 years',
    'Pre-K': '4–5 years',
    Schoolagers: '5–12 years',
  };
  const showcase = weeks.filter((week) => week.rooms);
  const picker = root.querySelector('#roomPicker');
  const grid = root.querySelector('#roomsGrid');

  const paintRooms = (number) => {
    const data = weeks[number - 1];
    if (!data?.rooms || !grid) return;
    grid.innerHTML = data.rooms.map((room) => {
      const color = roomColors[room.room] || '#A84B38';
      return `<article class="room" style="--rc:${color}"><div class="rAge">${roomAges[room.room] || ''}</div><h3 class="serif">${room.room}</h3><p>${room.text}</p></article>`;
    }).join('');
    Array.from(picker?.children || []).forEach((button) => {
      button.classList.toggle('on', Number(button.getAttribute('data-w')) === number);
    });
  };

  if (picker && grid && showcase.length) {
    picker.innerHTML = showcase.map((week, index) => (
      `<button type="button" class="rpBtn${index === 0 ? ' on' : ''}" data-w="${week.week}">${week.theme}</button>`
    )).join('');
    Array.from(picker.children).forEach((button) => {
      button.addEventListener('click', () => paintRooms(Number(button.getAttribute('data-w'))));
    });
    paintRooms(showcase[0].week);
  }

  roomLink?.addEventListener('click', () => {
    const number = Number(roomLink.getAttribute('data-w'));
    if (number) window.setTimeout(() => paintRooms(number), reducedMotion ? 0 : 260);
  });
})();
