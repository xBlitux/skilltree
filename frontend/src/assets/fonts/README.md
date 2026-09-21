# Roboto (lokal)

`Roboto-Variable.ttf` stammt unverändert aus dem offiziellen Google-Fonts-Repository:

- Schrift: https://github.com/google/fonts/blob/main/ofl/roboto/Roboto%5Bwdth%2Cwght%5D.ttf
- Lizenz: https://github.com/google/fonts/blob/main/ofl/roboto/OFL.txt
- Heruntergeladen am 21.09.2026; SIL Open Font License 1.1, siehe `OFL.txt`.

Die variable Schrift enthält die normalen Schnitte mit Gewichten 100–900. CSS bindet
sie über `@font-face` ein, Vite übernimmt sie beim Build nach `public/assets/`.
Es gibt keine externen Schriftanfragen und keine zusätzlich installierte Abhängigkeit.

Der Seitenfooter nennt den Copyright-Hinweis und verlinkt die vollständige `OFL.txt`.
Durch den URL-Import übernimmt Vite auch diese Lizenzdatei nach `public/assets/`.
Für diese Roboto-Version gilt SIL OFL 1.1, nicht Apache 2.0.
