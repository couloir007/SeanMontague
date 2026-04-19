/* global React */
const { useState } = React;

const Spec = ({ title, meta, children }) => (
  <div className="spec">
    <div className="spec-head">
      <h3 className="spec-title">{title}</h3>
      {meta && <span className="spec-meta">{meta}</span>}
    </div>
    <div className="spec-stage">{children}</div>
  </div>
);

const Section = ({ id, label, children }) => (
  <section id={id} className="section">
    <div className="section-label">{label}</div>
    {children}
  </section>
);

function ElementsSection() {
  return (
    <Section id="elements" label="Elements">
      <Spec title="button" meta="elements/button">
        <div style={{display:'flex',gap:14,alignItems:'center',flexWrap:'wrap'}}>
          <a href="#" className="button button--primary">Explore the Maps</a>
          <a href="#" className="button button--trail">Trail Variant</a>
          <a href="#" className="button button--ghost">Read the Writing</a>
        </div>
      </Spec>
      <Spec title="eyebrow" meta="elements/eyebrow">
        <div className="eyebrow">Burke, Vermont · Northeast Kingdom</div>
      </Spec>
      <Spec title="title" meta="elements/title">
        <h1 className="title title--display">KINGDOM TRAILS</h1>
        <h2 className="title title--h2" style={{marginTop:12}}>A Season of Skiing</h2>
        <h3 className="title title--h3" style={{marginTop:12}}>Builder, rider, grower.</h3>
      </Spec>
      <Spec title="byline / date / readtime" meta="elements/byline · date · readtime">
        <div style={{display:'flex',gap:20,alignItems:'center'}}>
          <span className="byline">Sean Montague</span>
          <span style={{color:'var(--border-dark)'}}>·</span>
          <span className="date">March 12, 2025</span>
          <span style={{color:'var(--border-dark)'}}>·</span>
          <span className="readtime">8 min read</span>
        </div>
      </Spec>
      <Spec title="quote" meta="elements/quote">
        <blockquote className="quote">
          <p className="quote__text">Burke doesn't have the terrain of Stowe, but it has something better — solitude, character, and a vertical that earns it.</p>
          <cite className="quote__cite">— Sean Montague · A Season of Skiing Burke</cite>
        </blockquote>
      </Spec>
      <Spec title="list" meta="elements/list">
        <ul className="list">
          <li>Smithsonian NMNH</li>
          <li>Drupal &amp; Twig</li>
          <li>Leaflet &amp; GIS</li>
          <li>Kingdom Trails regular</li>
        </ul>
      </Spec>
      <Spec title="pager" meta="elements/pager">
        <nav className="pager">
          <a href="#" className="pager__item pager__item--prev">
            <span className="pager__label">← Previous</span>
            <span className="pager__title">A Season of Skiing Burke</span>
          </a>
          <a href="#" className="pager__item pager__item--next">
            <span className="pager__label">Next →</span>
            <span className="pager__title">Year Three of the Food Forest</span>
          </a>
        </nav>
      </Spec>
      <Spec title="reading-progress" meta="elements/reading-progress">
        <div style={{height:2,background:'var(--border)',position:'relative'}}>
          <div className="reading-progress" />
        </div>
      </Spec>
    </Section>
  );
}

function ComponentsSection() {
  return (
    <Section id="components" label="Components">
      <Spec title="nav" meta="components/nav">
        <nav className="nav" style={{position:'relative'}}>
          <a href="#" className="nav__logo">Sean Montague <span>/ Burke, VT</span></a>
          <ul className="nav__links">
            <li><a href="#">Maps</a></li>
            <li><a href="#">Writing</a></li>
            <li><a href="#">Places</a></li>
            <li><a href="#">About</a></li>
          </ul>
        </nav>
      </Spec>
      <Spec title="breadcrumb" meta="components/breadcrumb">
        <nav className="breadcrumb">
          <ol className="breadcrumb__list">
            <li className="breadcrumb__item"><a href="#" className="breadcrumb__link">Home</a></li>
            <li className="breadcrumb__item"><a href="#" className="breadcrumb__link">Writing</a></li>
            <li className="breadcrumb__item"><span className="breadcrumb__current">First Ride at Kingdom Trails</span></li>
          </ol>
        </nav>
      </Spec>
      <Spec title="hero" meta="components/hero">
        <div className="hero">
          <div className="hero__overlay" style={{background:'linear-gradient(135deg, #e8e3d6 0%, #d4cdbc 100%)'}} />
          <div className="hero__content">
            <div className="hero__eyebrow">Burke, Vermont · Northeast Kingdom</div>
            <h1 className="hero__name">SEAN<br/>MONTAGUE</h1>
            <p className="hero__tagline">Maps, trails, and growing things. Web developer at the Smithsonian, living in the Northeast Kingdom.</p>
            <div className="hero__cta-row">
              <a href="#" className="button button--primary">Explore the Maps</a>
              <a href="#" className="button button--ghost">Read the Writing</a>
            </div>
          </div>
          <div className="hero__categories">
            <a href="#" className="hero__cat">Kingdom Trails</a>
            <a href="#" className="hero__cat">Burke Mountain</a>
            <a href="#" className="hero__cat">Permaculture</a>
            <a href="#" className="hero__cat">Maps</a>
          </div>
        </div>
      </Spec>
      <Spec title="marquee" meta="components/marquee">
        <div className="marquee">
          <div className="marquee__track">
            {Array(2).fill(0).map((_,i)=>(
              <React.Fragment key={i}>
                <span className="marquee__item">Kingdom Trails</span><span className="marquee__dot">·</span>
                <span className="marquee__item">Burke Mountain</span><span className="marquee__dot">·</span>
                <span className="marquee__item">Leaflet Mapping</span><span className="marquee__dot">·</span>
                <span className="marquee__item">Permaculture</span><span className="marquee__dot">·</span>
                <span className="marquee__item">Northeast Kingdom</span><span className="marquee__dot">·</span>
              </React.Fragment>
            ))}
          </div>
        </div>
      </Spec>
      <Spec title="card + cards-grid" meta="components/card">
        <div className="cards-grid">
          {[
            {c:'Kingdom Trails',k:'trails',t:'The Best Singletrack in Vermont Nobody Talks About',e:"Kingdom Trails has 100+ miles but most visitors ride the same ten.",m:'MAR 2025 · 8 MIN'},
            {c:'Permaculture',k:'garden',t:'Year Three of the Food Forest',e:"Planting in Zone 5 is an exercise in patience. Three years in.",m:'OCT 2024 · 10 MIN'},
            {c:'Maps',k:'maps',t:'Building a Trail Map with Leaflet and Drupal',e:"Notes from embedding interactive Leaflet maps in Drupal.",m:'SEP 2024 · 12 MIN'},
          ].map((it,i)=>(
            <a key={i} href="#" className="card">
              <span className={`card__category card__category--${it.k}`}>{it.c}</span>
              <h3 className="card__title">{it.t}</h3>
              <p className="card__excerpt">{it.e}</p>
              <span className="card__meta">{it.m}</span>
            </a>
          ))}
        </div>
      </Spec>
      <Spec title="places-card + places-grid" meta="components/places-card">
        <div className="places-grid">
          {[
            {s:'East Burke, Vermont',coords:'44.5965°N · 71.9105°W',n:'Kingdom Trails',d:'Over 100 miles of meticulously maintained singletrack in the hills above East Burke.',t:['Mountain Biking','Trail Running','Leaflet']},
            {s:'Burke, Vermont',coords:'44.5847°N · 71.9012°W',n:'Burke Mountain',d:'3,271 feet and 2,011 vertical feet of no-frills Vermont skiing.',t:['Skiing','Backcountry','Winter']},
          ].map((p,i)=>(
            <article key={i} className="places-card">
              <header className="places-card__meta">
                <span className="places-card__subtitle">{p.s}</span>
                <span className="places-card__coords">{p.coords}</span>
              </header>
              <h3 className="places-card__name">{p.n}</h3>
              <p className="places-card__desc">{p.d}</p>
              <div className="places-card__tags">{p.t.map(x=><span key={x} className="places-card__tag">{x}</span>)}</div>
            </article>
          ))}
        </div>
      </Spec>
      <Spec title="stats-bar" meta="components/stats-bar">
        <div className="stats-bar">
          {[
            {l:'Distance',v:'12.4',u:'MILES'},
            {l:'Elevation',v:'1,680',u:'FT GAIN'},
            {l:'Summit',v:'3,271',u:'FT'},
            {l:'Grade',v:'Moderate',u:'BLUE SQ',small:true},
            {l:'Time',v:'2:15',u:'H:MM'},
          ].map((s,i)=>(
            <div key={i} className={`stats-bar__item ${s.small?'stats-bar__item--small-value':''}`}>
              <span className="stats-bar__label">{s.l}</span>
              <span className="stats-bar__value">{s.v}</span>
              <span className="stats-bar__unit">{s.u}</span>
            </div>
          ))}
        </div>
      </Spec>
      <Spec title="input + form-item" meta="components/input · form-item">
        <div style={{maxWidth:420}}>
          <div className="form-item">
            <label className="form-item__label">Your email</label>
            <input className="input" type="email" placeholder="sean@example.com" />
            <div className="form-item__description">We'll never share it. Promise.</div>
          </div>
          <div className="form-item">
            <label className="form-item__label">Message</label>
            <textarea className="input" placeholder="Say hello…" />
          </div>
        </div>
      </Spec>
      <Spec title="tabs" meta="components/tabs">
        <div className="tabs">
          <ul className="tabs__list">
            <li><a className="tabs-tab tabs-tab--active" href="#">Overview</a></li>
            <li><a className="tabs-tab" href="#">Trails</a></li>
            <li><a className="tabs-tab" href="#">Conditions</a></li>
            <li><a className="tabs-tab" href="#">Related</a></li>
          </ul>
        </div>
      </Spec>
      <Spec title="messages" meta="components/messages">
        <div className="messages messages--status">Your ride log has been saved.</div>
        <div className="messages messages--warning">Trail conditions may be muddy through Friday.</div>
        <div className="messages messages--error">Map tiles failed to load. Check your connection.</div>
      </Spec>
      <Spec title="details" meta="components/details">
        <details className="details" open>
          <summary className="details__summary">Today's Conditions</summary>
          <div className="details__wrapper">54°F, calm wind, mixed ground. Trails firming up after three dry days.</div>
        </details>
      </Spec>
      <Spec title="taxonomy-term" meta="components/taxonomy-term">
        <article>
          <h2 className="taxonomy-term__name"><a href="#">Kingdom Trails</a></h2>
          <div className="taxonomy-term__content">The mountain-biking trail network above East Burke, Vermont. Over 100 miles of singletrack, maintained by the Kingdom Trails Association.</div>
        </article>
      </Spec>
      <Spec title="footer" meta="components/footer">
        <footer className="footer">
          <div className="footer__brand">Sean Montague · Burke, VT</div>
          <div className="footer__copy">© 2025 Sean Montague</div>
          <ul className="footer__links">
            <li><a href="#">GitHub</a></li>
            <li><a href="#">Drupal.org</a></li>
            <li><a href="#">LinkedIn</a></li>
          </ul>
        </footer>
      </Spec>
    </Section>
  );
}

function App() {
  const [section, setSection] = useState(() => localStorage.getItem('surface-kit-section') || 'elements');
  const pick = (s) => { setSection(s); localStorage.setItem('surface-kit-section', s); };

  const hierarchy = [
    { group: 'Base',       items: ['tokens · see colors_and_type.css', 'typography', 'prose'] },
    { group: 'Elements',   items: ['button','eyebrow','title','byline','date','readtime','quote','list','pager','reading-progress'], active: 'elements' },
    { group: 'Components', items: ['nav','breadcrumb','hero','marquee','card','places-card','stats-bar','input','form-item','tabs','messages','details','taxonomy-term','footer'], active: 'components' },
    { group: 'Collections',items: ['article','article-header','map-section','article-map-section'] },
    { group: 'Layouts',    items: ['site-header','site-footer','site-navigation','site-homepage'] },
    { group: 'Pages',      items: ['page-homepage','page-article'] },
  ];

  return (
    <>
      <aside className="sidebar">
        <div className="sidebar-head">
          <div className="sidebar-brand">Surface</div>
          <div className="sidebar-sub">Pattern Library · source/patterns</div>
        </div>
        <nav className="sidebar-nav">
          {hierarchy.map(g => (
            <div key={g.group} className="sidebar-group">
              <div className={`sidebar-group-label ${g.active === section ? 'is-active' : ''}`}
                   onClick={() => g.active && pick(g.active)}
                   style={{ cursor: g.active ? 'pointer' : 'default' }}>
                {g.group}
              </div>
              <ul className="sidebar-items">
                {g.items.map(it => (
                  <li key={it} className="sidebar-item">{it}</li>
                ))}
              </ul>
            </div>
          ))}
        </nav>
      </aside>
      <main className="main">
        <header className="main-head">
          <div>
            <div className="main-kicker">Surface / {section === 'elements' ? 'Elements' : 'Components'}</div>
            <h1 className="main-title">{section === 'elements' ? 'Elements' : 'Components'}</h1>
            <p className="main-sub">
              {section === 'elements'
                ? 'The smallest indivisible patterns — buttons, titles, quotes, pagers.'
                : 'Composed patterns — nav, hero, card, places-card, stats-bar, inputs, forms, messages.'}
            </p>
          </div>
          <div className="main-tabs">
            <button className={section === 'elements' ? 'tab active' : 'tab'} onClick={() => pick('elements')}>Elements</button>
            <button className={section === 'components' ? 'tab active' : 'tab'} onClick={() => pick('components')}>Components</button>
          </div>
        </header>
        {section === 'elements' ? <ElementsSection /> : <ComponentsSection />}
      </main>
    </>
  );
}

window.App = App;
