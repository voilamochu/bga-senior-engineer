# How to contribute to BGA Agricola

We welcome contributions from the community. Please read the following guidelines carefully to maximize the chances of your PR being merged.

1.  Ensure that you have joined the Discord channel, Agricola BGA Community, and obtained access to the code repository.
2.  Familiarize yourself with BGA's development process, using BGA studio for game testing. Note that you should create a new project, rather than uploading code and testing directly on the Agricola project.
3.  Before submitting a PR formally, you must read the introduction to the code structure below. Be careful when modifying public code and avoid changing the core code without special reasons.

## Introduction to current code base

1.  Most of the modifications to code logic are in the `modules/php` directory. The stability of the code decreases as you go down the list, meaning modifications should not be made without sufficient reason. If you must modify, please provide corresponding explanations in your PR.
    1.  `modules/php/Cards/A(B,C,D,E)` are primarily for the cards in each expansion, and are the main parts that get modified.
    2.  `modules/php/Actions` contains the most basic atomic abilities in the game, such as Sow, Occupation, etc., and are essential for understanding the game's listening mechanics.
    3.  `modules/php/Cards/Actions` includes the implementations for the game's action cards. Be aware that many action cards are implemented in different files for different player counts, do not forget to modify them together.
    4.  `modules/php/Managers` contains some of the main classes in the game, including a lot of core logic, card effects calculations, etc., and generally are not modified.
    5.  `modules/php/States` contains some states within the game, generally not modified.
    6.  `modules/php/Cards/Major` primarily developmental cards, generally not modified.
    7.  `modules/php/Core` the most core logic running the game engine, it's the most reliable code, and it should be the least modified.
2.  Other codes
    1.  `modules/php/DebugTrait.php` some functions added for easier debugging, which can execute commands such as play cards, draw cards, advance to a certain round, acquiring resources, etc., in the studio's dialog box.
    2.  `modules/css` & `agricola.scss` files involved in display styles, note that you need to generate the corresponding `agricola.css` file by the sass command after modifications.
    3.  `modules/js` todo
    4.  `states.inc.php` todo

## If I want to implement a new card, what should I do?

Currently, many mechanisms have been implemented, and most new cards are combinations of known mechanisms. Contributions to this charpter are welcomed.

1.  For effects that are triggered after performing a certain Action, typically you implement the `onPlayerAfterXXX` or `onOpponentAfterXXX` methods along with the corresponding `isListeningTo` listener. Here XXX is the name of the Action. See card B27_Toolbox for reference.
2.  For effects that are triggered before performing a certain Action, typically you implement the `onPlayerBeforeXXX` method and the corresponding `isListeningTo` listener, you may also need to implement `onPlayerIsDoable` to relax restrictions on whether an action can be performed. See card B109_PaperMaker for reference.
3.  SPECIAL_EFFECT Action can be used to implement special abilities. If user interaction is necessary to pass action parameters, you might need to modify both `modules/js/States/SpecialEffect.js` and `states.inc.php`. See card E4_Thunderbolt for reference.
4.  For actions that involve placing things on cards, you need to modify `modules/css/card.scss`. See cards E92_FieldDoctor and E123_ResourceHoarder for reference.
5.  For effects that involve a reduction or replacement of costs, typically you implement `onPlayerComputeCostsXXX` and `orderComputeCostsXXX`, where XXX is the name of the Action. The order of cost effectiveness is decided by the partial order relation defined in `orderComputeCostsXXX`. To date, there is no global unified definition location; it is scattered across different cards. Do not define opposing partial orders in different cards such as 'A<B' in card A while 'B<A' in card B. You can use DebugTrait's `checkCombos` to check for any global partial order definition contradictions. See cards B128_Plumber and D88_Millwright for reference.
6.  Clearly indicate the `pId` for the actions taken to prevent player action sequence from getting mixed up when inserting a settlement trigger for the opponent's action. See card E94_Prophet for reference.
7.  When implementing cards with effects triggered during an opponent's turn, you may need to specify `'forceConfirmation' => true`. See card A159_JoineroftheSea for reference.
8.  Use `Globals::setXXX` and `Globals::getXXX` to record and read global variables, usually for sharing some status with effects in other cards, or when some card effects need to start counting before being played. See card A136_DrudgeryReeve for reference.
9.  For cards that offer bonus points, typically you need to implement `computeBonusScore`. This implementation affects not only the final scoring but also real-time scoring. See card B153_Housemaster for reference.
10. For actions related to a turn, some card effects may require or settle within the same action round, use `setUsedOnTurnIdNode`. See card B29_CookeryLesson for reference.
11. For stacking resources on cards, key functions are `Meeples::createResourceInLocation` and `getNextResource`, also consider the note previously mentioned in point 4. See card A102_Grocer for reference.
12. To execute effects of other action cards, where this includes cards played by the players that can be used to perform actions, key function is `useActionSpaceNode`. See card D51_Archway for reference.
13. If effects involve calculating what is converted in the Exchange process, this is currently managed with a workaround using `onPlayerAtAnytime`. See card D56_FatstockStretcher for reference.
14. If avoiding executing effects on the action card but opting for other effects, you can implement `onPlayerComputePlaceFarmerFlow`. See card D138_PetLover for reference.
15. To change a certain gained Action, typically you implement the `onPlayerComputeReplaceXXX` method and corresponding `isListeningTo` listener. Generally, you also need to implement `onPlayerIsDoable` to relax restrictions on action execution, as well as `onPlayerComputeArgsPlaceFarmer` to ease restrictions on whether action cards can be selected. See card A97_Freshman for reference.