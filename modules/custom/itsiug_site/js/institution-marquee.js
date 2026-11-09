((Drupal, once) => {
  Drupal.behaviors.itsiugInstitutionMarquee = {
    attach(context) {
      once('itsiug-institution-marquee', '.grid-logos', context).forEach((section) => {
        const grid = section.querySelector('.grid-logos__grid');
        if (!grid || !grid.children.length) {
          return;
        }

        section.classList.add('grid-logos--itsiug-marquee');

        const track = document.createElement('div');
        track.className = 'grid-logos__track';
        const group = document.createElement('div');
        group.className = 'grid-logos__group';
        Array.from(grid.children).forEach((logo) => group.append(logo));

        const duplicate = group.cloneNode(true);
        duplicate.setAttribute('aria-hidden', 'true');
        duplicate.querySelectorAll('img').forEach((image) => image.setAttribute('alt', ''));
        duplicate.querySelectorAll('a').forEach((link) => link.setAttribute('tabindex', '-1'));

        track.append(group, duplicate);
        grid.replaceChildren(track);
      });
    }
  };
})(Drupal, once);
