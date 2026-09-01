// Ce fichier JS est à placer dans header.php/footer.php, CHARGÉ APRÈS le script CDN Sentry.
// Les autres fichiers .js sont individuels à chaque page/view, ils devront soigneusement être inscrits dans chaque view. On évite les erreurs de nom !

Sentry.init({
  dsn: "TON_DSN_FRONTEND_ICI",
  tracesSampleRate: 1.0,
});

document.addEventListener('DOMContentLoaded', () => {
});