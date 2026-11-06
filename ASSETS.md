# Artwork and other assets

## Background photographs

The following supplied files are adaptations of the linked Wikimedia Commons sources. The supplied originals were matched by SHA-1 where available; the source titles and licences were verified from Commons metadata. See ASSETS.json for source hashes and SHA-256 values of the bundled derivatives. Original filenames charlton-pitch/frampton were swapped in the prototype; the mapping below follows the actual source files.

Changes supplied with the prototype: resizing, cropping and muted colour treatment. Any project contributions to an adapted photograph are distributed under that photograph’s listed licence, including ShareAlike where applicable. The code MIT licence does not replace these notices.

| Bundled file | Source | Author | Licence |
| --- | --- | --- | --- |
| images/backgrounds/charlton-pitch.jpg | [Frampton United (8619719187).jpg](https://commons.wikimedia.org/wiki/File%3AFrampton_United_%288619719187%29.jpg) | grassrootsgroundswell | [CC BY 2.0](https://creativecommons.org/licenses/by/2.0) |
| images/backgrounds/frampton.jpg | [Football pitch and Charlton Court - geograph.org.uk - 3682477.jpg](https://commons.wikimedia.org/wiki/File%3AFootball_pitch_and_Charlton_Court_-_geograph.org.uk_-_3682477.jpg) | Marathon | [CC BY-SA 2.0](https://creativecommons.org/licenses/by-sa/2.0) |
| images/backgrounds/frogpool.jpg | [Frogpool and Cusgarne FC warming up. (8168211609).jpg](https://commons.wikimedia.org/wiki/File%3AFrogpool_and_Cusgarne_FC_warming_up._%288168211609%29.jpg) | grassrootsgroundswell | [CC BY 2.0](https://creativecommons.org/licenses/by/2.0) |
| images/backgrounds/hackney.jpg | [Hackney and Leyton match at Marshes 2021.jpg](https://commons.wikimedia.org/wiki/File%3AHackney_and_Leyton_match_at_Marshes_2021.jpg) | Timgeordie2017 | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |
| images/backgrounds/hooe.jpg | [Hooe Sports v Ninfield United (9738830845).jpg](https://commons.wikimedia.org/wiki/File%3AHooe_Sports_v_Ninfield_United_%289738830845%29.jpg) | grassrootsgroundswell | [CC BY 2.0](https://creativecommons.org/licenses/by/2.0) |
| images/backgrounds/ilminster.jpg | [Ilminster Town FC (8641758856).jpg](https://commons.wikimedia.org/wiki/File%3AIlminster_Town_FC_%288641758856%29.jpg) | grassrootsgroundswell | [CC BY 2.0](https://creativecommons.org/licenses/by/2.0) |
| images/backgrounds/kennington.jpg | [Kennington Playing Field - geograph.org.uk - 7985818.jpg](https://commons.wikimedia.org/wiki/File%3AKennington_Playing_Field_-_geograph.org.uk_-_7985818.jpg) | Steve Daniels | [CC BY-SA 2.0](https://creativecommons.org/licenses/by-sa/2.0) |
| images/backgrounds/kiveton.jpg | [KivetonHardLane.jpg](https://commons.wikimedia.org/wiki/File%3AKivetonHardLane.jpg) | Kivo | [CC0](http://creativecommons.org/publicdomain/zero/1.0/deed.en) |
| images/backgrounds/celebration.jpg | [LeeGaskellCelebrationMossley.jpg](https://commons.wikimedia.org/wiki/File%3ALeeGaskellCelebrationMossley.jpg) | Ian Williams | [CC BY-SA 3.0](https://creativecommons.org/licenses/by-sa/3.0) |
| images/backgrounds/hedinghams.jpg | [Hedinghams Utd - Sible Hedingham.JPG](https://commons.wikimedia.org/wiki/File%3AHedinghams%20Utd%20-%20Sible%20Hedingham.JPG) | Finnish Gas | [CC BY-SA 3.0](https://creativecommons.org/licenses/by-sa/3.0) |
| images/backgrounds/great-bentley.jpg | [Great Bentley FC - Changing Rooms.JPG](https://commons.wikimedia.org/wiki/File%3AGreat%20Bentley%20FC%20-%20Changing%20Rooms.JPG) | Finnish Gas | [CC BY-SA 3.0](https://creativecommons.org/licenses/by-sa/3.0) |

## Other assets

`splash.png`: cleaned Daladad Software branding, edited from the supplied splash with the built-in image editor to remove the small football and website line while retaining the logo panel; excluded from the code MIT licence. Brand/trademark rights are not granted. Replace it when rebranding a fork.

The three prototype backgrounds with no supplied attribution (huddle, spectators, keeper), the unmatched grasshoppers image and all “images for style” screenshots are excluded. Their display slots reuse credited photographs. The original OneDrive assets are unchanged.

No FPL manager data is bundled as artwork. Screenshots prepared during validation are local review artifacts and are not part of release ZIPs.

## Classic interface assets — 0.3.0

- `assets/jost.ttf`: unmodified variable Jost font from the Google Fonts repository, https://github.com/google/fonts/tree/main/ofl/jost . The bundled `assets/jost-OFL.txt` contains its copyright and SIL Open Font License. This open font approximates the reference's rounded interface lettering; it is not the original CM game font.
- `assets/profile-arrow.svg`: original code-drawn white pointer, covered by the source MIT licence. Used over passive content throughout the interface.
- `assets/classic-hand.svg`: original code-drawn white hand cursor, an approximation of the supplied style reference, covered by the source MIT licence. No screenshot pixels were copied.
- Pitch markings and optional personal direction arrows are drawn in CSS/SVG. No original game's screenshot pixels or font files were copied into the release.

In 0.6.0 both original cursor SVGs use smaller stepped outlines to better approximate the supplied game references. They remain original vector drawings, not extracted game assets; exact original cursor artwork is not claimed.
