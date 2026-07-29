# 如何为BGA Agricola贡献代码

我们欢迎来自社区的贡献。为了最大程度增加你的PR被接受的概率，请确保仔细阅读了下面的指引。

1. 确保你已经加入了Discord频道，Agricola BGA Community，并获得代码仓库权限。
2. 熟悉BGA的开发流程，使用BGA studio进行游戏测试，注意请新建项目，而不要直接在Agricola项目上上传代码和测试。
3. 正式提交PR前，务必阅读下面的代码结构介绍，修改公共代码时需要小心，没有特别的理由不要改动到核心代码。

## 当前codebase的简介

1. 大部分代码逻辑的修改都在modules/php目录下。代码稳定度，排名越往后越稳定，也就是说没有充分的理由不应当修改，如果要修改请在PR中提供对应的说明。
   1. modules/php/Cards/A(B,C,D,E) 主要是各个扩展中的卡牌，也是最主要的修改部分。
   2. modules/php/Actions 包含了游戏中最基础的原子能力的实现，比如Sow、Occupation等，是了解游戏监听机制必须掌握的内容。
   3. modules/php/Cards/Actions 包含了游戏中的行动卡牌的实现，注意在很多行动卡牌在不同人数时是不同的文件实现的，不要忘了一并修改。
   4. modules/php/Managers 游戏中一些主要的类，包含了大量的核心逻辑，卡牌效果计算等，一般也不太会修改。
   5. modules/php/States 游戏中的一些状态，一般不会修改。
   6. modules/php/Cards/Major 主要发展卡，一般不会修改
   7. modules/php/Core 游戏引擎运行的最核心逻辑，是目前最可靠的代码，也是最不应该修改的代码。
2. 其他代码
   1. modules/php/DebugTrait.php 方便调试添加的一些功能，可以在studio中的对话框输入命令执行如打牌、抽牌、前进到第几回合、获取资源等操作。
   2. modules/css & agricola.scss 一些涉及显示样式的文件，注意修改后需要通过sass命令生成对应的agricola.css文件。
   3. modules/js todo
   4. states.inc.php todo

## 如果我想实现一张新的卡牌，我应该怎么做？

目前已经有很多被实现的机制，大部分的新卡牌都是已知机制的组合。欢迎补充

1. 执行某个Action后触发的效果，一般来说实现`onPlayerAfterXXX`方法或者`onOpponentAfterXXX`，和对应的`isListeningTo`监听器即可，这里的XXX是Action的名字。参考卡牌B27_Toolbox。
2. 执行某个Action前触发的效果，一般来说实现`onPlayerBeforeXXX`方法和对应的`isListeningTo`监听器，同时需要注意可能需要额外实现`onPlayerIsDoable`来放松对行动是否能执行的限制。参考卡牌B109_PaperMaker。
3. 可以通过`SPECIAL_EFFECT`这个Action来实现特殊的能力，注意如果需要用户进行操作传递行动的参数可能需要同步修改modules/js/States/SpecialEffect.js和states.inc.php。参考卡牌E4_Thunderbolt。
4. 涉及卡牌上放东西的行为，需要修改modules/css/card.scss。参考卡牌E92_FieldDoctor，E123_ResourceHoarder。
5. 涉及费用减少，费用替换等效果，一般来说实现`onPlayerComputeCostsXXX`和`orderComputeCostsXXX`，这里的XXX是Action的名字，注意费用相关的生效顺序由`orderComputeCostsXXX`中定义的偏序关系决定，目前没有全局统一的定义处，而是分散在不同卡牌中，不要在不同的卡牌里定义相反的偏序关系，比如在A卡牌中说`A<B`，同时在B卡牌中说`B<A`。可以使用DebugTrait中的checkCombos来检查全局的偏序关系定义是否有矛盾。参考卡牌B128_Plumber，D88_Millwright。
6. 获得的行动尽量明确标注pId，防止在插入结算的触发对手行动后，玩家执行顺序错乱。参考卡牌E94_Prophet。
7. 实现在对手回合触发效果的卡牌时，可能需要标明`'forceConfirmation' => true`。参考卡牌A159_JoineroftheSea。
8. 可以使用`Globals::setXXX`和`Globals::getXXX`来记录和读取全局变量，一般用于和别的卡牌中的效果共享一些状态，或者某些卡牌效果需要在打出前就要开始计算的场景。参考卡牌A136_DrudgeryReeve。
9.  红利卡牌，一般来说需要实现`computeBonusScore`，主要该实现除了影响终局计分，也影响实时计分。参考卡牌B153_Housemaster。
10. turn相关的行动，有些卡牌效果可能涉及在同一个行动轮次内的要求或者结算，可以使用`setUsedOnTurnIdNode`。参考卡牌B29_CookeryLesson。
11. 卡牌上堆叠资源，关键函数`Meeples::createResourceInLocation`、`getNextResource`，同时需要注意上述第4点。参考卡牌A102_Grocer。
12. 执行其他行动卡牌效果，注意这里可能包含玩家打出的可以用来执行行的的卡牌，关键函数`useActionSpaceNode`。参考卡牌D51_Archway。
13. 涉及需要计算Exchange过程中转换了什么的效果，这里面目前的代码框架有坑，目前是通过一个`onPlayerAtAnytime`来进行work around。参考卡牌D56_FatstockStretcher。
14. 涉及不执行动牌上的效果改为执行其他的可以实现`onPlayerComputePlaceFarmerFlow`。参考卡牌D138_PetLover。
15. 更改某个获得的Action，一般来说实现`onPlayerComputeReplaceXXX`方法和对应的`isListeningTo`监听器，注意一般来说需要额外实现`onPlayerIsDoable`来放松对行动是否能执行的限制，以及`onPlayerComputeArgsPlaceFarmer`来放松对行动卡牌是否可以被选择的限制。参考卡牌A97_Freshman。
