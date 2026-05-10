const TEMPLATE = document.createElement('template');
TEMPLATE.innerHTML = `
  <style>
    :host { display: block; }
    .container { display: flex; flex-wrap: wrap; }
    .week { display: flex; flex-direction: column; }
    .day {
      margin: var(--aistma-heat-map-day-tile-margin, 1px);
      height: var(--aistma-heat-map-day-tile-height, 1rem);
      width: var(--aistma-heat-map-day-tile-width, 1rem);
      background-color: var(--aistma-heat-map-day-tile-default-background, #ebedf0);
    }
    .day[data-level="1"] { background-color: var(--aistma-heat-map-day-tile-one-quarter-background, #c6e48b); }
    .day[data-level="2"] { background-color: var(--aistma-heat-map-day-tile-two-quarters-background, #7bc96f); }
    .day[data-level="3"] { background-color: var(--aistma-heat-map-day-tile-three-quarters-background, #239a3b); }
    .day[data-level="4"] { background-color: var(--aistma-heat-map-day-tile-four-quarters-background, #196127); }
  </style>
  <div class="container"></div>
`;

customElements.define('aistma-heat-map', class extends HTMLElement {
  static get observedAttributes() { return ['start-date','weeks']; }

  constructor() {
    super();
    this._values = [];
    this.attachShadow({ mode: 'open' }).appendChild(TEMPLATE.content.cloneNode(true));
    this._container = this.shadowRoot.querySelector('.container');
    this.addEventListener('click', this._onClick.bind(this));
    this._msDay = 86400000;
  }

  attributeChangedCallback(name, oldVal, newVal) {
    if (oldVal !== newVal) this.render();
  }

  set values(arr) {
    this._values = arr.map(d => ({ date: new Date(d.date).getTime(), count: d.count }));
    this.render();
  }
  get values() { return this._values; }

  get startDate() {
    const attr = this.getAttribute('start-date');
    return attr ? new Date(attr).getTime() : Date.now() - this._msDay * 7 * 52;
  }
  get weeks() {
    const n = parseInt(this.getAttribute('weeks'));
    return isNaN(n) ? 52 : n;
  }

  render() {
    this._container.innerHTML = '';
    const max = Math.max(1, ...this._values.map(v => v.count));
    const map = new Map(this._values.map(v => [v.date, v.count]));

    for (let w = 0; w < this.weeks; w++) {
      const weekEl = document.createElement('div');
      weekEl.className = 'week';
      for (let d = 0; d < 7; d++) {
        const dayTs = this.startDate + ((w * 7 + d) * this._msDay);
        const count = map.get(dayTs) || 0;
        const level = Math.min(4, Math.ceil((count / max) * 4));

        const dayEl = document.createElement('div');
        dayEl.className = 'day';
        dayEl.dataset.level = level;
        dayEl.dataset.date = new Date(dayTs).toISOString();
        dayEl.dataset.count = count;
        dayEl.title = `${new Date(dayTs).toDateString()}: ${count}`;

        weekEl.appendChild(dayEl);
      }
      this._container.appendChild(weekEl);
    }
  }

  _onClick(e) {
    const el = e.target.closest('.day');
    if (!el) return;
    this.dispatchEvent(new CustomEvent('aistma-heat-map-day-selected', {
      detail: { date: el.dataset.date, contributions: +el.dataset.count },
      bubbles: true, composed: true
    }));
  }
});
