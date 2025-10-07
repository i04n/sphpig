# Simple PHP Image Gallery (SPHPIG)

SPHPIG is a PHP re-implementation of the static image gallery generator [Snig](https://github.com/domm/snig), originally brain coded in Perl by Thomas Klausner (domm@plix.at). The goal of this port is to provide the same workflow in PHP-centric environments while keeping the spirit and behaviour of the upstream project. It was vibecoded using an LLM.

## Features

- Scans directories for `jpg/jpeg` files.
- Generates thumbnails and preview images while preserving aspect ratio.
- Applies optional EXIF-based orientation fixes.
- Produces per-image HTML pages and a linked index.
- Packages originals into a downloadable ZIP archive.
- Ships with simple PHP templates and the CSS shared with the Perl project.

## Requirements

- PHP 7.4 or newer with the CLI SAPI.
- **GD** extension (required) for image resizing.
- **ZipArchive** extension (required) for creating the archive.
- **exif** extension (optional but recommended) for richer metadata extraction.

## Installation

1. Clone this repository or copy the `sphig` folder into your project.
2. Ensure the PHP executable you plan to use has the required extensions enabled.
3. (Optional) Add `sphpig/bin/snig.php` to your `PATH` or create a convenient alias.

## Usage

```bash
php bin/snig.php \
  --input /path/to/images \
  --output /path/to/gallery \
  --name "My Gallery"
```

### Command-line options

- `--name`  Human-friendly gallery name (defaults to the input directory name).
- `--th_size`  Thumbnail width in pixels (default: 200).
- `--detail_size`  Preview width in pixels (default: 1000).
- `--sort_by`  Sorting strategy: `created` (EXIF date) or `mtime` (filesystem modification time).
- `--force` Repeatable flag with values `resize` and/or `zip` to force image regeneration or ZIP creation even if artifacts already exist.

The output directory will contain:

- Copies of the originals (`orig_*`).
- Thumbnails (`thumbnail_*`) and previews (`preview_*`).
- An `index.html` page listing all images.
- Individual HTML pages per photo.
- The stylesheet `snig.css` and a ZIP archive with the original files.

## Credits

- **Upstream project:** Snig (Perl) by Thomas Klausner – https://github.com/domm/snig
- **PHP port:** Maintained in this repository, following the intent and licensing of the original.

## License

SPHPIG inherits the same license as Perl 5 (Artistic License 1.0 / GPL compatible). See the `LICENSE` file in the repository root for details.

## Contributing

Contributions are welcome. If you spot a bug, need support for a new format, or want to add features, please open an issue or submit a clearly described pull request.

---

Enjoy building static galleries with SPHPIG!
