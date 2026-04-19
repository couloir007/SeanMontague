/* global React */
const { useEffect, useRef, useState } = React;

/* ============================================================
   TRIP — collection (header + map + body/sidebar + destinations)
   Class prefix: tr-* (header/hero) and trip-* (shell sections)
   ============================================================ */

function TripPage({ trip, onBack }) {
  return (
    <div className="tr-root">
      <TripNav onBack={onBack} title={trip.title} />
      <TripHero header={trip.header} destinations={trip.destinations} />
      <TripStats stats={trip.header.stats} />
      <TripMapSection trip={trip} />
      <TripBody trip={trip} />
      <TripDestinations destinations={trip.destinations} />
      <TripFooter />
    </div>
  );
}

/* ── Nav ── */
function TripNav({ onBack, title }) {
  return (
    <nav className="tr-nav">
      <a href="#" className="tr-nav__logo" onClick={(e)=>{e.preventDefault(); onBack && onBack();}}>
        Sean&nbsp;Montague
      </a>
      <button className="tr-nav__back" onClick={onBack}>← All Trips</button>
    </nav>
  );
}

/* ── Hero (overlay + scrim) ── */
function TripHero({ header, destinations }) {
  return (
    <header className="tr-hero">
      <div
        className="tr-hero__image"
        style={{ backgroundImage: `url(${header.hero_image_src})` }}
        role="img"
        aria-label={header.hero_image_alt || header.title}
      />
      <div className="tr-hero__scrim" />

      {header.hero_image_credit && (
        <div className="tr-hero__credit">{header.hero_image_credit}</div>
      )}

      <div className="tr-hero__content">
        {header.category && (
          <div className="tr-hero__label">
            <span className={`tr-hero__cat tr-hero__cat--${header.category_key || 'travel'}`}>
              {header.category}
            </span>
            {header.region && <span className="tr-hero__region">· {header.region}</span>}
          </div>
        )}

        <h1 className="tr-hero__title">{header.title}</h1>

        {header.dates_label && (
          <div className="tr-hero__dates">{header.dates_label}</div>
        )}
      </div>

      <DestinationStrip destinations={destinations} />
    </header>
  );
}

/* ── Destination strip (horizontal scroll, nowrap) ── */
function DestinationStrip({ destinations }) {
  return (
    <div className="tr-strip">
      <div className="tr-strip__inner">
        {destinations.map((d, i) => (
          <React.Fragment key={d.slug}>
            <a href={`#${d.slug}`} className="tr-strip__name">{d.name}</a>
            {i < destinations.length - 1 && <span className="tr-strip__dot">·</span>}
          </React.Fragment>
        ))}
      </div>
    </div>
  );
}

/* ── Stats bar (Bebas 60px) ── */
function TripStats({ stats }) {
  if (!stats || !stats.length) return null;
  return (
    <section className="tr-stats">
      {stats.map((s, i) => (
        <div className="tr-stats__cell" key={i}>
          <div className="tr-stats__label">{s.label}</div>
          <div className="tr-stats__value">{s.value}</div>
          {s.unit && <div className="tr-stats__unit">{s.unit}</div>}
        </div>
      ))}
    </section>
  );
}

/* ── Map section ── */
function TripMapSection({ trip }) {
  const ref = useRef(null);

  useEffect(() => {
    if (!ref.current || !window.L) return;
    if (ref.current._leaflet_id) return; // already initialized

    const center = (trip.map_center || [53.3, -8.0]);
    const map = window.L.map(ref.current, { scrollWheelZoom: false, zoomControl: true })
      .setView(center, trip.map_zoom || 7);

    window.L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenTopoMap (CC-BY-SA), © OpenStreetMap',
      maxZoom: 17,
    }).addTo(map);

    const latlngs = [];
    (trip.destinations || []).forEach((d, i) => {
      if (!d.coords) return;
      const [lat, lon] = d.coords;
      latlngs.push([lat, lon]);
      const idx = String(i + 1).padStart(2, '0');
      const icon = window.L.divIcon({
        className: 'tr-marker',
        html: `<span class="tr-marker__pin"></span><span class="tr-marker__num">${idx}</span>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
      });
      window.L.marker([lat, lon], { icon })
        .addTo(map)
        .bindTooltip(`${idx} ${d.name}`, { direction: 'top', offset: [0, -10] });
    });

    if (latlngs.length > 1) {
      window.L.polyline(latlngs, { color: '#3a5a40', weight: 3, dashArray: '6 6' }).addTo(map);
      map.fitBounds(latlngs, { padding: [40, 40] });
    }
  }, [trip]);

  return (
    <section className="trip-map-section" id="route">
      <div className="trip-map-section__header">
        <span className="trip-map-section__label">The Route</span>
        <span className="trip-map-section__note">Drag to pan · scroll on map to zoom</span>
      </div>
      <div className="trip-map-section__map" ref={ref} />
    </section>
  );
}

/* ── Body + itinerary sidebar ── */
function TripBody({ trip }) {
  return (
    <section className="trip-wrap">
      <div className="trip__body">
        {trip.narrative_kicker && (
          <div className="trip__kicker">{trip.narrative_kicker}</div>
        )}
        {trip.narrative_lede && (
          <h2 className="trip__lede">{trip.narrative_lede}</h2>
        )}
        <div dangerouslySetInnerHTML={{ __html: trip.body }} />
      </div>

      <aside className="trip__sidebar">
        <div className="trip__sidebar-header">Itinerary</div>
        <ul className="trip__itinerary">
          {trip.destinations.map((d, i) => (
            <li key={d.slug}>
              <a href={`#${d.slug}`} className="trip__itinerary-item">
                <span className="trip__itinerary-num">{String(i + 1).padStart(2, '0')}</span>
                <span className="trip__itinerary-body">
                  <span className="trip__itinerary-place">{d.name}</span>
                  {(d.region || d.dates) && (
                    <span className="trip__itinerary-meta">
                      {d.region}{d.region && d.dates && ' · '}{d.dates}
                    </span>
                  )}
                </span>
              </a>
            </li>
          ))}
        </ul>
      </aside>
    </section>
  );
}

/* ── Destinations grid (coords placeholder, not fake mini-maps) ── */
function TripDestinations({ destinations }) {
  return (
    <section className="trip-destinations">
      <div className="trip-destinations__header">
        <h2 className="trip-destinations__title">Destinations</h2>
        <span className="trip-destinations__count">{destinations.length} stops</span>
      </div>
      <div className="trip-destinations__grid">
        {destinations.map((d, i) => (
          <a key={d.slug} id={d.slug} href={`#${d.slug}`} className="trip-dest-card">
            <div className="trip-dest-card__coord">
              <div className="trip-dest-card__coord-num">{String(i + 1).padStart(2, '0')}</div>
              {d.coords_label && (
                <div
                  className="trip-dest-card__coord-text"
                  dangerouslySetInnerHTML={{ __html: d.coords_label }}
                />
              )}
            </div>
            <div className="trip-dest-card__body">
              <div className="trip-dest-card__name">{d.name}</div>
              {d.region && <div className="trip-dest-card__region">{d.region}</div>}
            </div>
            <span className="trip-dest-card__arrow">→</span>
          </a>
        ))}
      </div>
    </section>
  );
}

function TripFooter() {
  return (
    <footer className="sm-footer">
      <div className="sm-footer__inner">
        <div className="sm-footer__brand">Sean Montague</div>
        <div className="sm-footer__meta">© 2026 · Burke, Vermont</div>
      </div>
    </footer>
  );
}

window.TripPage = TripPage;
