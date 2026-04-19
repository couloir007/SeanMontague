/* global React */
function ArticleCard({ category, categoryClass, title, excerpt, meta, onOpen }) {
  return (
    <a href="#" onClick={(e) => { e.preventDefault(); onOpen && onOpen(); }} className="sm-card">
      <span className={`sm-card__cat sm-card__cat--${categoryClass}`}>{category}</span>
      <h3 className="sm-card__title">{title}</h3>
      <p className="sm-card__excerpt">{excerpt}</p>
      <span className="sm-card__meta">{meta}</span>
    </a>
  );
}

function ArticleGrid({ onOpenArticle }) {
  const items = [
    { c: 'Kingdom Trails', k: 'trails', t: 'The Best Singletrack in Vermont Nobody Talks About', e: "Kingdom Trails has 100+ miles but most visitors ride the same ten. Here's what's hiding in the back of the map.", m: 'March 2025 · 8 min read' },
    { c: 'Burke Mountain', k: 'trails', t: 'A Season of Skiing Burke: Notes from a Local', e: "Burke doesn't have the terrain of Stowe but it has something better — solitude, character, and a vertical that earns it.", m: 'February 2025 · 6 min read' },
    { c: 'Permaculture',   k: 'garden', t: 'Year Three of the Food Forest: What Actually Worked', e: "Planting a food forest in Zone 5 Vermont is an exercise in patience and humility. Three years in, here's the honest report.", m: 'October 2024 · 10 min read' },
    { c: 'Maps',           k: 'maps',   t: 'Building a Trail Map with Leaflet and Drupal', e: "Notes from embedding interactive Leaflet maps in Drupal using custom GeoJSON fields and Twig.", m: 'September 2024 · 12 min read' },
    { c: 'Travel',         k: 'travel', t: 'Three Days in the Burren Without a Plan', e: "Notes on the limestone coast of western Ireland, a place that rewards aimless walking more than any itinerary.", m: 'July 2024 · 8 min read' },
    { c: 'Drupal',         k: 'tech',   t: 'GeoJSON as a Drupal Field Type: A Case Study', e: "Notes from building a Drupal module for storing and rendering GeoJSON geometries.", m: 'March 2024 · 9 min read' },
  ];
  return (
    <section className="sm-section sm-cards-section" id="writing">
      <span className="sm-section__label">Writing</span>
      <div className="sm-cards-grid">
        {items.map((it, i) => (
          <ArticleCard key={i} category={it.c} categoryClass={it.k} title={it.t} excerpt={it.e} meta={it.m} onOpen={onOpenArticle} />
        ))}
      </div>
    </section>
  );
}

function PlaceCard({ num, subtitle, title, desc, tags }) {
  return (
    <div className="sm-place">
      <span className="sm-place__num">{num}</span>
      <p className="sm-place__sub">{subtitle}</p>
      <h3 className="sm-place__title">{title}</h3>
      <p className="sm-place__desc">{desc}</p>
      <div className="sm-place__tags">{tags.map(t => <span key={t} className="sm-tag">{t}</span>)}</div>
    </div>
  );
}

function PlaceGrid() {
  const places = [
    { n: '01', s: 'East Burke, Vermont', t: 'Kingdom Trails', d: 'Over 100 miles of meticulously maintained singletrack in the hills above East Burke. One of the best mountain bike trail networks in North America — and genuinely my backyard.', tags: ['Mountain Biking', 'Trail Running', 'Leaflet Maps', 'GeoJSON'] },
    { n: '02', s: 'Burke, Vermont',      t: 'Burke Mountain',  d: '3,271 feet and 2,011 vertical feet of no-frills Vermont skiing. Local knowledge matters here.', tags: ['Skiing', 'Backcountry', 'Terrain Maps', 'Winter Routes'] },
    { n: '03', s: 'Burke, Vermont',      t: 'The Home Place',  d: 'A small homestead in Burke where I\'m building a food forest and practicing permaculture design in Zone 5.', tags: ['Permaculture', 'Food Forest', 'Zone 5', 'Perennials'] },
    { n: '04', s: 'Smithsonian Institution · Washington, DC', t: 'Natural History Museum', d: 'Web developer at the Smithsonian National Museum of Natural History, working on Drupal and interactive mapping.', tags: ['Drupal', 'Twig', 'SVG / D3', 'Open Source'] },
  ];
  return (
    <section className="sm-section sm-places-section" id="places">
      <span className="sm-section__label">Places &amp; Projects</span>
      <div className="sm-places-grid">
        {places.map(p => <PlaceCard key={p.n} num={p.n} subtitle={p.s} title={p.t} desc={p.d} tags={p.tags} />)}
      </div>
    </section>
  );
}

function AboutSection() {
  const skills = ['Smithsonian NMNH', 'Drupal & Twig', 'Leaflet & GIS / Mapping', 'SVG & Data Visualization', 'JavaScript / Open Source', 'Permaculture Design', 'Kingdom Trails regular'];
  return (
    <section className="sm-section sm-about" id="about">
      <span className="sm-section__label">About</span>
      <div className="sm-about__grid">
        <div>
          <h2 className="sm-about__title">Builder, rider, <em>grower.</em></h2>
          <ul className="sm-about__skills">{skills.map(s => <li key={s}>{s}</li>)}</ul>
        </div>
        <div className="sm-about__body">
          <p>I'm Sean Montague — a web developer based in Burke, Vermont, in the Northeast Kingdom. I work at the Smithsonian National Museum of Natural History, building Drupal systems, interactive maps, and data visualization tools.</p>
          <p>This site is a personal outlet for the things I care about outside of work: riding Kingdom Trails, skiing Burke Mountain, building a food forest, and making maps of the places I know well.</p>
          <p>The technical stuff — Leaflet, GeoJSON, Twig, SVG — bleeds into the personal projects too. That's fine by me.</p>
        </div>
      </div>
    </section>
  );
}

function ContactSection() {
  return (
    <section className="sm-section sm-contact" id="contact">
      <span className="sm-section__label" style={{ color: 'rgba(255,255,255,0.28)' }}>Contact</span>
      <h2 className="sm-contact__headline">Say hello.</h2>
      <p className="sm-contact__sub">Always happy to talk trails, maps, or growing things.</p>
      <a href="mailto:sean@seanmontague.com" className="sm-contact__email">sean@seanmontague.com</a>
    </section>
  );
}

function Footer() {
  return (
    <footer className="sm-footer">
      <div className="sm-footer__brand">Sean Montague · Burke, VT</div>
      <div className="sm-footer__copy">© 2025 Sean Montague</div>
      <ul className="sm-footer__links">
        <li><a href="#" onClick={(e) => e.preventDefault()}>GitHub</a></li>
        <li><a href="#" onClick={(e) => e.preventDefault()}>Drupal.org</a></li>
        <li><a href="#" onClick={(e) => e.preventDefault()}>LinkedIn</a></li>
      </ul>
    </footer>
  );
}

window.ArticleCard = ArticleCard;
window.ArticleGrid = ArticleGrid;
window.PlaceCard = PlaceCard;
window.PlaceGrid = PlaceGrid;
window.AboutSection = AboutSection;
window.ContactSection = ContactSection;
window.Footer = Footer;
