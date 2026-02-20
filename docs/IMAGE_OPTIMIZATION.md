# Image Optimization Guidelines

## Image Requirements

### Property Images
- Format: WebP (with JPEG fallback)
- Max file size: 500KB per image
- Dimensions: 1920x1080 (landscape) or 1080x1920 (portrait)
- Quality: 80-85%

### Thumbnails
- Dimensions: 400x300
- Max file size: 100KB

## Tools for Optimization

### Online Tools
- TinyPNG: https://tinypng.com
- Squoosh: https://squoosh.app

### Command Line
```bash
# Install ImageMagick
brew install imagemagick  # macOS
apt-get install imagemagick  # Linux

# Optimize image
convert input.jpg -quality 85 -resize 1920x1080 output.jpg
```

## Implementation Steps

1. Resize images before upload
2. Convert to WebP format
3. Keep JPEG as fallback
4. Use lazy loading for images below the fold

## Best Practices

- Always compress images before uploading to the server
- Use descriptive filenames (e.g., `asokoro-2bed-living-room.jpg`)
- Test images on mobile devices for quality
- Consider using a CDN for image delivery in production
