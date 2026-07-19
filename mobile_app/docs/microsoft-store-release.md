# Microsoft Store release

Angel Granites is registered in Partner Center as an **MSIX or PWA app**.

## Product identity

- Package identity name: `AngelStones.AngelGranites`
- Publisher: `CN=A2F5AA89-FCBF-4A75-9905-C57DA605A1BD`
- Publisher display name: `Angel Stones`
- Store ID: `9NPQBXKDHPML`

These public Store identity values are mirrored in `pubspec.yaml` under
`msix_config`. They must continue to match Partner Center exactly.

## Build the package

Windows is required to compile a Flutter Windows application. Run the
**Build Microsoft Store MSIX** workflow manually in GitHub Actions. It:

1. Uses Flutter stable 3.44.6 on a Windows 2022 runner.
2. Runs analysis and all Flutter tests.
3. Builds the x64 release application and unsigned Store MSIX.
4. Uploads `AngelGranites-MicrosoftStore-MSIX` as a workflow artifact.
5. Uploads `AngelGranites-Windows-Accessibility-Test`, a portable ZIP that can
   be run on Windows without Visual Studio for keyboard, Narrator, contrast,
   and DPI verification.

Download the workflow artifact, extract the `.msix`, and upload it under the
Partner Center submission's **Packages** section. The Microsoft Store signs the
package after certification; do not add a private signing certificate to this
repository.

## Submission checklist

- Pricing and availability: choose the intended markets, audience, schedule,
  and base price.
- Properties: use the website privacy policy and business contact details.
- Age ratings: complete every required question.
- Packages: upload the generated MSIX and keep device family limited to
  Windows Desktop unless another family is explicitly tested.
- Store listing: description, features, at least one screenshot, and required
  Store logos.
- Submission options: add certification notes explaining that customer portal
  and payment pages open in the system browser on Windows.

Increment the semantic version in `pubspec.yaml` before every later Store
submission. The MSIX tool maps `major.minor.patch+build` to
`major.minor.patch.0`, and Partner Center requires each replacement package to
have a higher package version.

Do not select the Store accessibility declaration until the manual checks in
`windows-accessibility-audit.md` have passed on the portable Windows build.
