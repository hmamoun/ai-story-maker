# AdSense Shortcode Usage

The AI Story Maker plugin includes a shortcode for easy AdSense integration in your posts and pages.

## Basic Usage

### Default AdSense Code
```
[aistma_adsense]
```

This will render the default AdSense code with the hardcoded client ID and slot.

### Custom Attributes

You can customize the AdSense display with the following attributes:

```
[aistma_adsense client="ca-pub-6861474761481747" slot="8915797913" format="in-article" style="display:block; text-align:center;"]
```

## Available Attributes

| Attribute | Default Value | Description |
|-----------|---------------|-------------|
| `client` | `ca-pub-6861474761481747` | Your AdSense publisher ID |
| `slot` | `8915797913` | Your AdSense ad slot ID |
| `format` | `in-article` | Ad layout format (in-article, in-feed, etc.) |
| `style` | `display:block; text-align:center;` | Custom CSS styles for the ad container |

## Examples

### Basic In-Article Ad
```
[aistma_adsense]
```

### Custom Styled Ad
```
[aistma_adsense style="display:block; text-align:center; margin: 20px 0;"]
```

### Different Ad Format
```
[aistma_adsense format="in-feed" style="display:block; text-align:center;"]
```

## Security Features

- All attributes are properly escaped using `esc_attr()`
- Uses WordPress's `shortcode_atts()` for safe attribute parsing
- Follows WordPress coding standards

## Integration Tips

1. **Placement**: Insert the shortcode where you want the ad to appear in your content
2. **Testing**: Test the shortcode in a draft post before publishing
3. **Performance**: The AdSense script is loaded asynchronously for better page performance
4. **Compliance**: Ensure your AdSense implementation complies with Google's AdSense policies

## Troubleshooting

- If ads don't appear, check that your AdSense account is approved
- Verify that the client ID and slot ID are correct
- Ensure your site complies with AdSense policies
- Check browser console for any JavaScript errors

## Related Topics to Learn

- **WordPress Shortcodes**: Learn more about creating custom shortcodes
- **AdSense Optimization**: Best practices for ad placement and user experience
- **Content Monetization**: Strategies for effective ad integration
- **WordPress Security**: Understanding proper escaping and sanitization

---

**Extra Pro Debugging Tip**: Use browser developer tools to inspect the rendered AdSense code and verify that all attributes are properly set. You can also use the WordPress debug log to track shortcode execution by adding logging statements to the shortcode function. 