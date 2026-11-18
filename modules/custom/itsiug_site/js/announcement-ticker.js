((Drupal, once) => {
  Drupal.behaviors.itsiugAnnouncementTicker = {
    attach(context) {
      const actions = document.querySelector('.hero.hero--themed .hero__actions');
      const hero = actions?.closest('.hero');
      if (!hero) {
        return;
      }

      once('itsiug-announcement-ticker', '.itsiug-announcement-ticker', context).forEach((ticker) => {
        ticker.classList.add('itsiug-announcement-ticker--hero-overlay');
        hero.append(ticker);
      });
    }
  };
})(Drupal, once);
