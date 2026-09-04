// Ce fichier JS est à placer dans header.php/footer.php, CHARGÉ APRÈS le script CDN Sentry.
// Les autres fichiers .js sont individuels à chaque page/view, ils devront soigneusement être inscrits dans chaque view. On évite les erreurs de nom !

Sentry.init({
  dsn: "https://11c97357c696b3c26ef5c86b25d1c547@o4512011460214784.ingest.de.sentry.io/4512027244363856",
  tracesSampleRate: 1.0,
});

document.addEventListener('DOMContentLoaded', () => {
});