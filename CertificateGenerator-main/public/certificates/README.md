The real `fla-seal.jpg` was extracted from the source PDF and is used by the generated certificates. The background artwork uses `certificate-template.svg` because this PHP install does not have GD, and Dompdf cannot render PNG files without GD.

If you need to override the seal, place a replacement `fla-seal.png` here. The certificate view checks that first, then falls back to the extracted `fla-seal.jpg`, then `fla-seal.svg`.
