/* global React */
/* ============================================================
   Destination grid — three alternative approaches, matched to
   the Sean Montague kit vocabulary (forest / stone / bright,
   font-display / serif / mono, left-border hover rails).
   Each component takes `destinations` and renders a different
   section you can drop into trip.html.
   ============================================================ */

/* ───────────────────────────────────────────────────────────
   APPROACH A · INDEX LIST
   Editorial full-width list. Row = Num · Name · Region · Dates · Coords.
   Forest-green left rail on hover. Dense, reads like a TOC —
   best fit when you have 10+ destinations.
   ─────────────────────────────────────────────────────────── */
function DestGridIndex({ destinations }) {
  return (
    <section className="destA">
      <div className="destA__header">
        <span className="destA__label">Index</span>
        <h2 className="destA__title">All <em>destinations</em></h2>
        <span className="destA__count">{destinations.length} stops</span>
      </div>

      <ol className="destA__list">
        {destinations.map((d, i) => (
          <li key={d.slug} className="destA__row">
            <a href={`#${d.slug}`} className="destA__link">
              <span className="destA__num">{String(i + 1).padStart(2, '0')}</span>
              <span className="destA__name">{d.name}</span>
              <span className="destA__region">{d.region || '—'}</span>
              <span className="destA__dates">{d.dates || '—'}</span>
              <span className="destA__coords">
                {d.coords ? `${d.coords[0].toFixed(3)}° N · ${Math.abs(d.coords[1]).toFixed(3)}° W` : ''}
              </span>
              <span className="destA__arrow">→</span>
            </a>
          </li>
        ))}
      </ol>
    </section>
  );
}

/* ───────────────────────────────────────────────────────────
   APPROACH B · STAGGERED TIMELINE
   Single dashed forest line running top-to-bottom, destinations
   alternating left/right of the line. Each item has a bigger
   moment: name in display face, region in mono, dates on the
   opposite side. Reinforces sequence.
   ─────────────────────────────────────────────────────────── */
function DestGridTimeline({ destinations }) {
  return (
    <section className="destB">
      <div className="destB__header">
        <span className="destB__label">Route · in order</span>
        <h2 className="destB__title">The <em>itinerary</em></h2>
      </div>

      <div className="destB__track">
        <div className="destB__line" aria-hidden="true" />
        {destinations.map((d, i) => {
          const side = i % 2 === 0 ? 'left' : 'right';
          return (
            <a
              key={d.slug}
              href={`#${d.slug}`}
              className={`destB__node destB__node--${side}`}
            >
              <span className="destB__dot" aria-hidden="true" />
              <div className="destB__card">
                <div className="destB__num">{String(i + 1).padStart(2, '0')}</div>
                <div className="destB__name">{d.name}</div>
                <div className="destB__region">{d.region}</div>
              </div>
              <div className="destB__aside">
                <div className="destB__dates">{d.dates}</div>
                {d.coords && (
                  <div className="destB__coords">
                    {d.coords[0].toFixed(3)}° N<br/>
                    {Math.abs(d.coords[1]).toFixed(3)}° W
                  </div>
                )}
              </div>
            </a>
          );
        })}
      </div>
    </section>
  );
}

/* ───────────────────────────────────────────────────────────
   APPROACH C · MAP-ANCHORED LIST
   Two-column. Sticky Leaflet map on left with numbered pins.
   Scrollable destination list on right. Hover an item to
   highlight its pin; click to fly-to.
   ─────────────────────────────────────────────────────────── */
function DestGridMapAnchored({ destinations, center = [53.3, -8.0], zoom = 7 }) {
  const mapEl = React.useRef(null);
  const mapRef = React.useRef(null);
  const markersRef = React.useRef({});
  const [active, setActive] = React.useState(null);

  React.useEffect(() => {
    if (!mapEl.current || !window.L) return;
    if (mapEl.current._leaflet_id) return;

    const map = window.L.map(mapEl.current, { scrollWheelZoom: false, zoomControl: true })
      .setView(center, zoom);
    mapRef.current = map;

    window.L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenTopoMap (CC-BY-SA), © OpenStreetMap',
      maxZoom: 17,
    }).addTo(map);

    const latlngs = [];
    destinations.forEach((d, i) => {
      if (!d.coords) return;
      latlngs.push(d.coords);
      const idx = String(i + 1).padStart(2, '0');
      const icon = window.L.divIcon({
        className: 'destC-marker',
        html: `<span class="destC-marker__pin"></span><span class="destC-marker__num">${idx}</span>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15],
      });
      const marker = window.L.marker(d.coords, { icon })
        .addTo(map)
        .bindTooltip(`${idx} ${d.name}`, { direction: 'top', offset: [0, -12] });
      markersRef.current[d.slug] = marker;
    });

    if (latlngs.length > 1) {
      window.L.polyline(latlngs, { color: '#3a5a40', weight: 3, dashArray: '6 6' }).addTo(map);
      map.fitBounds(latlngs, { padding: [40, 40] });
    }
  }, []);

  const flyTo = (d) => {
    if (mapRef.current && d.coords) {
      mapRef.current.flyTo(d.coords, 10, { duration: 0.8 });
    }
    setActive(d.slug);
  };

  return (
    <section className="destC">
      <div className="destC__header">
        <span className="destC__label">Map · Atlas view</span>
        <h2 className="destC__title">Fourteen <em>stops</em></h2>
        <span className="destC__count">{destinations.length}</span>
      </div>

      <div className="destC__layout">
        <div className="destC__mapWrap">
          <div className="destC__map" ref={mapEl} />
        </div>

        <ol className="destC__list">
          {destinations.map((d, i) => {
            const isActive = active === d.slug;
            return (
              <li
                key={d.slug}
                className={`destC__item${isActive ? ' is-active' : ''}`}
                onMouseEnter={() => setActive(d.slug)}
                onMouseLeave={() => setActive(null)}
                onClick={() => flyTo(d)}
              >
                <span className="destC__num">{String(i + 1).padStart(2, '0')}</span>
                <div className="destC__body">
                  <div className="destC__name">{d.name}</div>
                  <div className="destC__meta">
                    <span>{d.region}</span>
                    {d.dates && <span>· {d.dates}</span>}
                  </div>
                </div>
                {d.coords && (
                  <div className="destC__coords">
                    {d.coords[0].toFixed(2)}°<br/>
                    {Math.abs(d.coords[1]).toFixed(2)}°
                  </div>
                )}
              </li>
            );
          })}
        </ol>
      </div>
    </section>
  );
}

window.DestGridIndex = DestGridIndex;
window.DestGridTimeline = DestGridTimeline;
window.DestGridMapAnchored = DestGridMapAnchored;
