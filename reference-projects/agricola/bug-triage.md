# Bug triage log

Canonical tracking for BGA bug-report triage. One section per bug, keyed by BGA bug ID.
Raw report dumps (`bugspage*.json`) are gitignored; this file is the durable record.

**Verdicts:** `not-ours` (platform), `already-fixed`, `working-as-intended`, `confirmed` (live bug, root cause known), `needs-repro`, `probably-fixed` (fix exists, unverified against report).
**Status:** `triaged` → `repro-written` → `verified` / `fixed` → `commented` → `closed`.

## Summary

| Bug | Votes | Subject | Verdict | Status | Next action |
|---|---|---|---|---|---|
| [69145](#69145) | 13 | Images fail to load, kicked out | not-ours | triaged | comment + close |
| [156671](#156671) | 10 | Undo veg→food then Clay Pipe → freeze | already-fixed | triaged | verify in browser, comment |
| [88262](#88262) | 10 | Salter no preview during breeding arrange | needs-repro | triaged | attempt repro (card-held newborns?) |
| [160692](#160692) | 9 | Clay Pipe click without resources → locked out | probably-fixed | triaged | verify in browser, fix residual A53:174 |
| [125640](#125640) | 9 | Generic can't-act / server error | not-ours | triaged | comment + close |
| [108007](#108007) | 8 | Generic can't-confirm (incl. Catan comment) | not-ours | triaged | comment + close |
| [142468](#142468) | 8 | Hammer Crusher + Plumber/Trowel renovate gate | confirmed | triaged | debug_repro142468, fix |
| [76353](#76353) | 8 | Pet Lover suppresses Ravenous Hunger bonus | confirmed | triaged | debug_repro76353, fix |
| [101913](#101913) | 7 | Reclamation Plow never offers plow | confirmed | triaged | debug_repro101913, fix |
| [78483](#78483) | 7 | Bed in the Grain Field growth only once | working-as-intended | triaged | comment + close |

---

## 69145
**Report:** access, 2022. Images fail to load, red errors, player kicked (Chinese users; F5 makes loading slower).
**Verdict:** not-ours. CDN/connectivity failure; game code cannot cause asset-load + disconnect. Comments span 2022–2024 with no common game state.
**Dupes:** none tracked.

## 156671
**Report:** block, 2025-02. Feeding phase: convert vegetable→food, Undo, click Clay Pipe → GS12 error, table wedged.
**Verdict:** already-fixed by `ffbf2778` (2025-09-27, "Fixed fatal claypipe or empty button after mandatory harvest conversions"). Root cause: zero-gain Clay Pipe reaction rendered as blank clickable SPECIAL_EFFECT button; clicking after Undo threw against rewound flow tree, reaction re-fired on every reload. All confirming comments predate the fix (last 2025-06-22).
**Residual:** `A53_Claypipe.php:174` `if ($n >= 0)` always true — zero-gain work-phase exchanges still emit the empty node server-side; only the client guard (`agricola.js:530-532`) hides it. Latent: `SpecialEffect.php:74` unqualified `BgaVisibleSystemException` inside `AGR\Actions` namespace → would be PHP fatal if reached (currently unreachable).
**Dupes:** 160692 (same artifact).

## 88262
**Report:** display, 2023-05. Using Salter mid-breeding-arrangement doesn't visually remove the consumed parent pre-confirm (Fireplace does).
**Verdict:** needs-repro, leaning already-mitigated. `B157_Salter.php:27-30` gates the anytime window on `getAnimals('reserve')->count() == 0`, and pending newborns sit in reserve — sequence should be unreachable. But the guard predates the report, so something reached it in 2023.
**Repro question:** any path where newborns aren't in `reserve` (animal-holder cards? non-harvest breeding?). If reachable, fix = client preview in `SpecialEffect.js` `B157_add` mirroring Fireplace's DOM move (`ReorganizeAnimals.js:274`).

## 160692
**Report:** access, 2025-03. Clicked Clay Pipe without 7 resources → error, table inaccessible ~7 min (BGA state timeout), several dupes through 2025-08.
**Verdict:** probably-fixed by `ffbf2778` (same blank-button artifact as 156671), low confidence — verify on current code.
**Dupes:** 156671 (canonical for the fix).

## 125640
**Report:** block, 2024-05. "Can't play any action / server report error."
**Verdict:** not-ours. Grab-bag: comments over a year describe unrelated symptoms (game not starting, confirm timeout, GS1 slow-request). No common game state; platform load/notif issues. Our own notif-queue wedge (see draft-blocked investigation) could contribute some instances but nothing actionable from these reports.

## 108007
**Report:** access, 2023-12. "Confirm button does nothing."
**Verdict:** not-ours. Cross-game pile-on — one comment is a **Catan** table (#456780050). Platform-level confirm/turn-timer issues.

## 142468
**Report:** action, 2024-10. Hammer Crusher room build before renovating with Plumber (or Trowel) discount → "You must make sure you have enough resources to renovate to stone" blocks a legal line.
**Verdict:** confirmed live bug.
**Root cause:** `D14_HammerCrusher.php:104-113` (`stoneRenovationCheck`, wrapped in at `Actions/Construct.php:134` on the opponent-triggered path) prices renovation via ctx-less `Actions::get(RENOVATION)->getCosts($player, 'roomStone', $cost)`. With null ctx, `actionCardId` is null (`Renovation.php:34`), so discount listeners bail: Plumber `B128_Plumber.php:43`, Trowel `D13_Trowel.php:49`. Guard demands full price.
**Fix direction:** capture the pending renovation's `actionCardId` into extra datas alongside `costs` (set in `isListeningTo`, D14:55) and thread it into `getCosts`. Also check `countRooms()` at D14:109 pre- vs post-build (the check runs after the room is built).
**Git:** guard introduced `921e8f55` (2023-09-30); untouched behaviourally since.

## 76353
**Report:** rules, 2022-11. Ravenous Hunger send to Sheep Market, choose Pet Lover option → RH's +1 sheep not granted. "Used to work."
**Verdict:** confirmed live regression since `4b993436` (2022-09-05).
**Root cause:** Pet Lover was reworked from listener to flow rewriter: `D138_PetLover.php:29-66` replaces the flow with a NODE_XOR whose alternative branch is a plain `gainNode` — no COLLECT node, so no AfterCollect event, so `C42_RavenousHunger.php:75` never fires. Animal Dealer stacks fine because it listens to PlaceFarmer (`A147:22-27`, fired at `PlaceFarmer.php:170` before flow compute).
**Fix direction:** in Pet Lover's alternative branch, fire a collect-equivalent event — fix in D138, not C42, since all `onPlayerAfterCollect` cards on 1-animal spaces are affected (e.g. D146_Porter, A146_StorehouseSteward).
**Rules note:** code treats the space as still "providing exactly 1" animal with RH active (physical-meeples count, `D138:36-41`); the alternative reading (+1 makes it 2 → Pet Lover disabled) is not implemented. Current interpretation matches the reporter's expectation.

## 101913
**Report:** rules, 2023-10. Reclamation Plow: cook animals, then collect + accommodate all newly taken animals → plow never offered.
**Verdict:** confirmed live bug.
**Root cause:** `A17_ReclamationPlow.php:92-103` compares `countAnimalsOnBoard()` before/after at AfterCollect time, but collected animals are still in `reserve` (`Meeples.php:581`) — never counted by `Player.php:496-505`. A17's presence also makes auto-reorganize early-out placing nothing (`Reorganize.php:192-199`), and the deferred REORGANIZE resolves after the listener. So `A17:94` `if ($animalsOnBoard[$type] < $amt) return;` silently suppresses the offer unless the player already owned ≥ that many of the type pre-collect. Both reports cooked their only animals first.
**Fix direction:** count reserve+board at A17:92, or evaluate after the REORGANIZE node resolves. Check `B34_SpecialFood` for the same pattern.
**Note:** D138/C148 mentioned by a reporter are incidental (destination holders), not causal.

## 78483
**Report:** rules, 2023-01. "Bed of Wheat" (C24 Bed in the Grain Field) family growth offered at first harvest but not later ones despite conditions met.
**Verdict:** working-as-intended. Card text: "At the start of **the next** harvest, you get a Family Growth action if you have room" — one-shot. `C24:38-43` gates on `!isFlagged()`; `flagCardNode()` sets `used=1` permanently. Ruling string added in `648bb900` (2026-02-05): "Only works in the next harvest after it is played."
**Note:** flag is set before the freeRoom-gated growth node, so the card is consumed even if no room — matches the physical card, but explains the round-13/14 report if they lacked a free room slot.
