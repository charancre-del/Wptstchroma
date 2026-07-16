# Management Fact Verification List

**Audit date:** 2026-07-16
**Purpose:** Prevent unsupported claims from advancing beyond staging.
**Production rule:** Do not update production/live claims without documented approval and a separate release authorization.

## Current Evidence Rule

Published records and rendered staging copy prove that a claim exists; they do not prove that the claim is true. The current live and staging exports each contain 24 published `location` records, but "published" does not establish active-campus status.

| Fact or claim | Current staging evidence | Approval needed | Safe staging fallback | Status |
| --- | --- | --- | --- | --- |
| Active campus count | Home renders 24+; About and Employers render 19+; both record inventories contain 24 location posts | Active public-campus roster and effective date | "Locations across Metro Atlanta" | Current conflict; management approval required |
| Founding date and origin | About renders `Established 2022` and `over the last decade` | Approved origin timeline and exact story | "Chroma began in Georgia with a commitment to warm, high-quality early learning." | Current conflict; management approval required |
| Family/local ownership | About renders "locally owned"; legal ownership evidence is not in this audit folder | Legal/brand-approved phrase and scope | Omit ownership structure | Management approval required |
| Parent communication platform | Home exposes Brightwheel and LineLeader references; Parents exposes Procare | Current platform by campus and transition status | "Families receive updates through the campus parent communication platform." | Current conflict; management approval required |
| Company-wide operating hours | Home and Parents expose both 6:00 and 6:30 time claims; campus schedules may vary | Campus hours source and whether any universal statement is valid | "Hours vary by campus; contact your preferred location for current hours." | Current conflict; management approval required |
| CAPS acceptance | Home says some campuses accept CAPS; prior issue list includes broader language elsewhere | Participating campuses, authorization, eligibility, and availability | "CAPS may be accepted at participating campuses, subject to authorization and availability." | Management approval required |
| GA Pre-K availability | Current site promotes GA Pre-K; universal participation is not established by the inventory | Participating campuses, eligibility, enrollment, and approved state wording | "Georgia Pre-K may be available to eligible families at participating campuses." | Management approval required |
| NAEYC recognition | Home renders `NAEYC Recognized` as a broad trust badge | Recognized entity/campus/program and proof source | Remove the badge and broad claim | Current claim; management approval required |
| GAC accreditation | Home renders `GAC Accredited GA Pre-K` as a broad trust badge | Accredited entity/program/campus and proof source | Remove the badge and broad claim | Current claim; management approval required |
| "Accredited excellence" | Home renders company-wide accredited positioning | Exact accredited scope and supporting document | "Quality-focused early learning" | Current claim; management approval required |
| Early Start Speech/OT/ABA services | Early Start renders Speech, Occupational Therapy, and ABA service language | Current providers, campuses, eligibility, insurance, and enrollment status | "Services and eligibility vary. Contact Chroma Early Start for current availability." | Current claim; management approval required |
| Licensed clinicians in academy classrooms | Prior audit found broad classroom implications; current inventory does not establish scope | Campuses/programs where clinicians are present and approved wording | "Some families may access additional developmental services through Chroma Early Start." | Retest context; management approval required |
| Tuition and fees | No universal current pricing source is present in the audit evidence | Campus/program price source and effective date | "Contact your preferred campus for current tuition and fees." | Management approval required |
| Late fees and policies | Current Parents page includes a late-pickup policy and 6:00 PM closing statement; governing handbook version is absent | Governing handbook/terms version and campus scope | "Campus policies and hours may vary; request the current handbook." | Current claim; management approval required |
| Review rating | Home renders a 4.8 average parent rating | Review platform, review count, calculation, date, and attribution rules | Remove numeric average; retain attributed individual reviews | Current claim; management approval required |
| Families served | Home renders 4,000+ families served | Source, definition, date range, and calculation owner | Remove the number or use approved dated wording | Current claim; management approval required |
| "5-star rated" generated claims | Prior generator audit found generic rating language; current rendered family was not fully content-reviewed | Rating source by location and update process | Remove generic generated rating claim | Retest required; management approval required |
| "Free GA Pre-K" generated claims | Prior generator audit found broad free-program wording; current 530 URLs were tested for HTTP, not claim accuracy | Eligibility, participating campuses, and approved state language | "Georgia Pre-K may be available to eligible families at participating campuses." | Retest required; management approval required |

## Required Approval Record

For each approved fact, record:

- Approver name and role
- Approval date
- Exact approved wording
- Scope: company, campus, program, or service
- Source document or public verification URL
- Effective date and review/expiration date
- Owner responsible for future updates

## Release Guardrail

Until an approval record exists, use the safe staging fallback or remove the claim. This audit does not authorize any production content change.
