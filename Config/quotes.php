<?php

/**
 * Quote library.
 *
 * Notes:
 * - IDs are stable so achievements can reference a quote permanently.
 * - All quotes are original text (no copyrighted quotes).
 */
return [
    // Quote selection helpers.
    // We keep the full library of quotes below, and we also provide
    // pre-defined "tone" buckets to enable rarity-aware selection.
    //
    // Notes:
    // - "tone" is optional per achievement. If set, we prefer that bucket.
    // - If not set, we use rarity preferences below.
    // - Buckets reference quote IDs from the library.
    'buckets' => [
        // Snappy, humorous, a bit cheeky.
        'funny' => array_map(function ($i) {
            return sprintf('q%03d', $i);
        }, range(1, 70)),
        // Heroic / energetic / “let’s go”.
        'epic' => array_map(function ($i) {
            return sprintf('q%03d', $i);
        }, range(71, 120)),
        // Reflective, life-ish, “this meant something”.
        'philosophical' => array_map(function ($i) {
            return sprintf('q%03d', $i);
        }, range(121, 150)),
    ],

    // Rarity-driven tone preferences.
    // Higher rarity = more epic/philosophical by default.
    'rarity_preferences' => [
        'common' => ['funny', 'epic', 'philosophical'],
        'rare' => ['epic', 'funny', 'philosophical'],
        'epic' => ['epic', 'philosophical', 'funny'],
        'legendary' => ['philosophical', 'epic', 'funny'],
    ],

    // Optional: mailbox-aware tone preferences.
    // This lets different teams get different “vibes” by default.
    //
    // IMPORTANT:
    // - This only affects auto-selection of quote IDs when creating/editing trophies.
    // - It does NOT restrict who can earn a trophy.
    // - Quote uniqueness is still enforced at the trophy level when possible.
    //
    // Example:
    // 'mailbox_preferences' => [
    //   1 => ['tones' => ['funny','epic']],           // Customer Care
    //   2 => ['tones' => ['epic','philosophical']],   // Tech Escalations
    // ],
    'mailbox_preferences' => [
        // mailbox_id => ['tones' => ['funny','epic','philosophical']]
    ],

    // Optional: mailbox-aware quote libraries.
    // Use this if you want *different subsets* of the 150 quotes to be used
    // for different mailboxes/teams.
    //
    // How it works:
    // - If a trophy has mailbox_id set AND the mailbox has a library list here,
    //   auto-selection is restricted to that subset first.
    // - Uniqueness is still enforced at the trophy level when possible.
    // - If the subset is exhausted (or empty), we fall back to the global library.
    //
    // Example:
    // 'mailbox_libraries' => [
    //   1 => ['q001','q002','q003', /* ... */], // Customer Care
    //   2 => ['q071','q072','q073', /* ... */], // Tech Escalations
    // ],
    'mailbox_libraries' => [
        // mailbox_id => ['q001','q002', ...]
    ],

    // A large pool used for per-achievement unique quotes.
    "library" => [
        ["id" => "q001", "text" => 'Reality tried. You replied. Reality blinked first.', "author" => ""],
        ["id" => "q002", "text" => 'You turned confusion into a checklist. That\'s wizardry.', "author" => ""],
        ["id" => "q003", "text" => 'Some heroes wear capes. You wear a ticket queue.', "author" => ""],
        ["id" => "q004", "text" => 'Today you were the calm in someone else’s storm.', "author" => ""],
        ["id" => "q005", "text" => 'Support is basically time travel: you fix the future before it happens.', "author" => ""],
        ["id" => "q006", "text" => 'You didn’t just solve a problem—you reduced entropy.', "author" => ""],
        ["id" => "q007", "text" => 'Congratulations: you have successfully negotiated with the universe.', "author" => ""],
        ["id" => "q008", "text" => 'Patience: 10/10. Skills: 10/10. Keyboard: still alive.', "author" => ""],
        ["id" => "q009", "text" => 'Your brain just did parkour over a weird edge case.', "author" => ""],
        ["id" => "q010", "text" => 'Every closed ticket is one less gremlin in the machine.', "author" => ""],
        ["id" => "q011", "text" => 'Small win, big momentum. Physics approves.', "author" => ""],
        ["id" => "q012", "text" => 'You are now officially overqualified for this particular chaos.', "author" => ""],
        ["id" => "q013", "text" => 'Nice. The bug is gone and your dignity remains intact.', "author" => ""],
        ["id" => "q014", "text" => 'You delivered clarity like it was express shipping.', "author" => ""],
        ["id" => "q015", "text" => 'The customer is happier. The servers are quieter. The cosmos nods.', "author" => ""],
        ["id" => "q016", "text" => 'Your focus was so sharp it could cut through meeting notes.', "author" => ""],
        ["id" => "q017", "text" => 'A clean resolution is a love letter to Future You.', "author" => ""],
        ["id" => "q018", "text" => 'You found the needle, then politely explained the haystack.', "author" => ""],
        ["id" => "q019", "text" => 'Your logic just did a backflip and landed perfectly.', "author" => ""],
        ["id" => "q020", "text" => 'Somewhere, a spreadsheet sighed in relief.', "author" => ""],
        ["id" => "q021", "text" => 'You made the impossible look like a Tuesday task.', "author" => ""],
        ["id" => "q022", "text" => 'You brought order to a place that strongly preferred disorder.', "author" => ""],
        ["id" => "q023", "text" => 'Even your errors have excellent error handling.', "author" => ""],
        ["id" => "q024", "text" => 'You just leveled up your ability to tame nonsense.', "author" => ""],
        ["id" => "q025", "text" => 'One more trophy. One less mystery.', "author" => ""],
        ["id" => "q026", "text" => 'Your perseverance is a feature, not a bug.', "author" => ""],
        ["id" => "q027", "text" => 'That wasn’t luck. That was reps.', "author" => ""],
        ["id" => "q028", "text" => 'You fixed it once. Now it’s fixed forever (we hope).', "author" => ""],
        ["id" => "q029", "text" => 'Your solution has vibes: clean, confident, slightly smug.', "author" => ""],
        ["id" => "q030", "text" => 'You turned \'uh-oh\' into \'all good.\'', "author" => ""],
        ["id" => "q031", "text" => 'Deep breath. You did the thing. Again.', "author" => ""],
        ["id" => "q032", "text" => 'Your workflow just got a tiny bit legendary.', "author" => ""],
        ["id" => "q033", "text" => 'You’re collecting wins like they’re limited edition.', "author" => ""],
        ["id" => "q034", "text" => 'You debugged reality and pushed the patch to production.', "author" => ""],
        ["id" => "q035", "text" => 'Somewhere a future outage just got cancelled.', "author" => ""],
        ["id" => "q036", "text" => 'You made a complicated thing behave. That\'s rare magic.', "author" => ""],
        ["id" => "q037", "text" => 'Your brain deserves a standing ovation and a snack.', "author" => ""],
        ["id" => "q038", "text" => 'Another day, another dragon politely escorted out.', "author" => ""],
        ["id" => "q039", "text" => 'You fought the ticket. The ticket lost.', "author" => ""],
        ["id" => "q040", "text" => 'Your calm is doing unpaid overtime. Respect.', "author" => ""],
        ["id" => "q041", "text" => 'You didn’t panic. You practiced.', "author" => ""],
        ["id" => "q042", "text" => 'Elegance achieved: minimal drama, maximal results.', "author" => ""],
        ["id" => "q043", "text" => 'You are the reason the helpdesk has hope.', "author" => ""],
        ["id" => "q044", "text" => 'This is what competence looks like: quiet, steady, inevitable.', "author" => ""],
        ["id" => "q045", "text" => 'Your attention to detail is borderline supernatural.', "author" => ""],
        ["id" => "q046", "text" => 'You just turned a complaint into a compliment. Alchemy!', "author" => ""],
        ["id" => "q047", "text" => 'Your keyboard is basically a wand at this point.', "author" => ""],
        ["id" => "q048", "text" => 'You took the scenic route and still arrived early.', "author" => ""],
        ["id" => "q049", "text" => 'That fix was so clean it could be framed.', "author" => ""],
        ["id" => "q050", "text" => 'The queue is slightly less scary because you exist.', "author" => ""],
        ["id" => "q051", "text" => 'Your mind just did a satisfying \'click.\'', "author" => ""],
        ["id" => "q052", "text" => 'You solved it and didn’t even break a sweat (we’ll pretend).', "author" => ""],
        ["id" => "q053", "text" => 'A good day to be efficient and mildly unstoppable.', "author" => ""],
        ["id" => "q054", "text" => 'You moved fast *and* made sense. Rare combo.', "author" => ""],
        ["id" => "q055", "text" => 'Not all heroes close tickets, but you sure do.', "author" => ""],
        ["id" => "q056", "text" => 'You just earned another notch on the belt of competence.', "author" => ""],
        ["id" => "q057", "text" => 'Your consistency is loud even when you’re quiet.', "author" => ""],
        ["id" => "q058", "text" => 'One ticket at a time, you’re rewriting the odds.', "author" => ""],
        ["id" => "q059", "text" => 'You did the hard part: you started.', "author" => ""],
        ["id" => "q060", "text" => 'Your progress is compounding like nerdy interest.', "author" => ""],
        ["id" => "q061", "text" => 'That was craftsmanship. Respect the craft.', "author" => ""],
        ["id" => "q062", "text" => 'You took a messy situation and gave it a haircut.', "author" => ""],
        ["id" => "q063", "text" => 'Your discipline called. It said \'nice work.\'', "author" => ""],
        ["id" => "q064", "text" => 'You didn’t just respond—you responded *well.*', "author" => ""],
        ["id" => "q065", "text" => 'Today’s chaos tried to recruit you. You declined.', "author" => ""],
        ["id" => "q066", "text" => 'Your system thinking is showing. It’s glorious.', "author" => ""],
        ["id" => "q067", "text" => 'You’re building trust one resolution at a time.', "author" => ""],
        ["id" => "q068", "text" => 'Somewhere a user just said: \'Wow, that was fast.\'', "author" => ""],
        ["id" => "q069", "text" => 'You’re the human version of a successful retry.', "author" => ""],
        ["id" => "q070", "text" => 'You turned uncertainty into a plan. Again.', "author" => ""],
        ["id" => "q071", "text" => 'That’s the sound of future-you saying thank you.', "author" => ""],
        ["id" => "q072", "text" => 'Your momentum has momentum now.', "author" => ""],
        ["id" => "q073", "text" => 'You made the weird thing not weird anymore.', "author" => ""],
        ["id" => "q074", "text" => 'Precision. Calm. Victory.', "author" => ""],
        ["id" => "q075", "text" => 'You’re not grinding. You’re forging.', "author" => ""],
        ["id" => "q076", "text" => 'A little better today means a lot better later.', "author" => ""],
        ["id" => "q077", "text" => 'You didn’t win by speed. You won by clarity.', "author" => ""],
        ["id" => "q078", "text" => 'Your instincts are getting suspiciously accurate.', "author" => ""],
        ["id" => "q079", "text" => 'Your brain just filed that under \'handled.\'', "author" => ""],
        ["id" => "q080", "text" => 'You practiced the boring part. Now it’s powerful.', "author" => ""],
        ["id" => "q081", "text" => 'That was a masterclass in not making it worse.', "author" => ""],
        ["id" => "q082", "text" => 'Your troubleshooting has plot armor.', "author" => ""],
        ["id" => "q083", "text" => 'You taught the system new manners.', "author" => ""],
        ["id" => "q084", "text" => 'You handled the ticket with the gentleness of a pro.', "author" => ""],
        ["id" => "q085", "text" => 'Congrats: you just upgraded your future patience.', "author" => ""],
        ["id" => "q086", "text" => 'Your work is invisible, but your impact isn’t.', "author" => ""],
        ["id" => "q087", "text" => 'You turned a problem into documentation bait. Beautiful.', "author" => ""],
        ["id" => "q088", "text" => 'One more step toward becoming the local legend.', "author" => ""],
        ["id" => "q089", "text" => 'Your skills are slowly becoming unfair.', "author" => ""],
        ["id" => "q090", "text" => 'That was a clean fix. Chef’s kiss.', "author" => ""],
        ["id" => "q091", "text" => 'You made the user feel seen. That’s the real win.', "author" => ""],
        ["id" => "q092", "text" => 'You just turned \'stuck\' into \'solved\'.', "author" => ""],
        ["id" => "q093", "text" => 'Your attention is a flashlight in a dark forest of edge cases.', "author" => ""],
        ["id" => "q094", "text" => 'You don’t need motivation. You have momentum.', "author" => ""],
        ["id" => "q095", "text" => 'Somewhere, a bug is writing your name in its diary.', "author" => ""],
        ["id" => "q096", "text" => 'You are the adult supervision the internet needs.', "author" => ""],
        ["id" => "q097", "text" => 'You just saved someone 30 minutes of frustration. Hero.', "author" => ""],
        ["id" => "q098", "text" => 'Resolve: strong. Drama: minimal. Perfect.', "author" => ""],
        ["id" => "q099", "text" => 'You’re making the complex feel approachable.', "author" => ""],
        ["id" => "q100", "text" => 'Your consistency is basically a superpower in disguise.', "author" => ""],
        ["id" => "q101", "text" => 'You did the thing even when it wasn’t fun. That’s growth.', "author" => ""],
        ["id" => "q102", "text" => 'Your solutions are getting… elegant. Suspiciously elegant.', "author" => ""],
        ["id" => "q103", "text" => 'You made a decision and reality complied.', "author" => ""],
        ["id" => "q104", "text" => 'You didn’t just answer—you *guided.*', "author" => ""],
        ["id" => "q105", "text" => 'You walked into confusion and left with a map.', "author" => ""],
        ["id" => "q106", "text" => 'You’re stacking small wins into a fortress.', "author" => ""],
        ["id" => "q107", "text" => 'That’s one more trophy for the museum of competence.', "author" => ""],
        ["id" => "q108", "text" => 'You kept your cool. The ticket did not.', "author" => ""],
        ["id" => "q109", "text" => 'Your patience could be used as a renewable energy source.', "author" => ""],
        ["id" => "q110", "text" => 'You’re writing a quiet epic, ticket by ticket.', "author" => ""],
        ["id" => "q111", "text" => 'You found the root cause. The root cause is now unemployed.', "author" => ""],
        ["id" => "q112", "text" => 'You are building a reputation with every resolved thread.', "author" => ""],
        ["id" => "q113", "text" => 'Your focus just punched through distractions.', "author" => ""],
        ["id" => "q114", "text" => 'That was a thoughtful fix, not a frantic fix.', "author" => ""],
        ["id" => "q115", "text" => 'You did good work. The kind that lasts.', "author" => ""],
        ["id" => "q116", "text" => 'You made someone’s day less annoying. That counts.', "author" => ""],
        ["id" => "q117", "text" => 'You are becoming the person future problems fear.', "author" => ""],
        ["id" => "q118", "text" => 'You made a messy system behave like it had standards.', "author" => ""],
        ["id" => "q119", "text" => 'Your craftsmanship is showing. Keep it.', "author" => ""],
        ["id" => "q120", "text" => 'You didn’t quit. You iterated.', "author" => ""],
        ["id" => "q121", "text" => 'You solved it, and you learned something. Double win.', "author" => ""],
        ["id" => "q122", "text" => 'The universe remains absurd. You remain effective.', "author" => ""],
        ["id" => "q123", "text" => 'You are the calm operator in a world of panic clicks.', "author" => ""],
        ["id" => "q124", "text" => 'You just leveled up your ability to be unbothered.', "author" => ""],
        ["id" => "q125", "text" => 'Your progress is real, measurable, and mildly intimidating.', "author" => ""],
        ["id" => "q126", "text" => 'You didn’t just reply fast—you replied smart.', "author" => ""],
        ["id" => "q127", "text" => 'You were kind to the user and strict with the bug.', "author" => ""],
        ["id" => "q128", "text" => 'You made the right thing easy and the wrong thing irrelevant.', "author" => ""],
        ["id" => "q129", "text" => 'You’re not chasing perfection. You’re building excellence.', "author" => ""],
        ["id" => "q130", "text" => 'That fix was a tiny act of hope.', "author" => ""],
        ["id" => "q131", "text" => 'You improved the system. The system improved your future.', "author" => ""],
        ["id" => "q132", "text" => 'You turned friction into flow.', "author" => ""],
        ["id" => "q133", "text" => 'Somewhere, a future incident report just got shorter.', "author" => ""],
        ["id" => "q134", "text" => 'You handled it with grace and a dash of menace (to bugs).', "author" => ""],
        ["id" => "q135", "text" => 'You moved the needle. The needle is grateful.', "author" => ""],
        ["id" => "q136", "text" => 'Your work is quiet, but it echoes.', "author" => ""],
        ["id" => "q137", "text" => 'You stayed curious. Curiosity wins again.', "author" => ""],
        ["id" => "q138", "text" => 'You made a plan and followed it. Radical.', "author" => ""],
        ["id" => "q139", "text" => 'Your discipline is basically a cheat code.', "author" => ""],
        ["id" => "q140", "text" => 'You didn’t need magic. You had method.', "author" => ""],
        ["id" => "q141", "text" => 'Your confidence is built from receipts, not vibes.', "author" => ""],
        ["id" => "q142", "text" => 'You made the queue smaller. The universe smiles.', "author" => ""],
        ["id" => "q143", "text" => 'You chose clarity over cleverness. That’s real skill.', "author" => ""],
        ["id" => "q144", "text" => 'You turned a question into understanding.', "author" => ""],
        ["id" => "q145", "text" => 'Your brain just did a neat little alignment.', "author" => ""],
        ["id" => "q146", "text" => 'You didn’t just fix the symptom—you healed the cause.', "author" => ""],
        ["id" => "q147", "text" => 'You kept going. That’s the whole secret.', "author" => ""],
        ["id" => "q148", "text" => 'Your momentum is contagious. Sorry, team.', "author" => ""],
        ["id" => "q149", "text" => 'You are slowly becoming unstoppable in a very polite way.', "author" => ""],
        ["id" => "q150", "text" => 'You handled today’s chaos with tomorrow’s wisdom.', "author" => ""],
    ],

    // Legacy themed pools (kept for backwards compatibility; not required for per-achievement mode).
    'first_win' => [
        ['id' => 'fw1', 'text' => 'The first ticket is the hardest. After that, it\'s just physics.', 'author' => ''],
        ['id' => 'fw2', 'text' => 'You converted chaos into smaller, more polite chaos.', 'author' => ''],
        ['id' => 'fw3', 'text' => 'Beginner\'s luck is real. So is beginner\'s grit.', 'author' => ''],
    ],
    'consistency' => [
        ['id' => 'c1', 'text' => 'Motivation is weather. Habits are climate.', 'author' => ''],
        ['id' => 'c2', 'text' => 'Tiny wins, stacked, become gravity.', 'author' => ''],
        ['id' => 'c3', 'text' => 'You showed up. The universe respects that.', 'author' => ''],
    ],
    'quality' => [
        ['id' => 'q1', 'text' => 'Fix it once. Sleep forever.', 'author' => ''],
        ['id' => 'q2', 'text' => 'Anyone can close a ticket. You closed the loop.', 'author' => ''],
        ['id' => 'q3', 'text' => 'A clean resolution is a love letter to Future You.', 'author' => ''],
    ],
    'speed' => [
        ['id' => 's1', 'text' => 'Fast is fine. Clear is divine.', 'author' => ''],
        ['id' => 's2', 'text' => 'Velocity is kindness when it\'s paired with clarity.', 'author' => ''],
        ['id' => 's3', 'text' => 'Response time matters — composure matters more.', 'author' => ''],
    ],
    'mastery' => [
        ['id' => 'm1', 'text' => 'Expertise is just mistakes with better documentation.', 'author' => ''],
        ['id' => 'm2', 'text' => 'You\'re not grinding. You\'re forging.', 'author' => ''],
        ['id' => 'm3', 'text' => 'Skill is what remains after the panic leaves.', 'author' => ''],
    ],
    'generic' => [
        ['id' => 'g1', 'text' => 'Progress: now with slightly more wisdom per minute.', 'author' => ''],
        ['id' => 'g2', 'text' => 'Small steps. Big momentum.', 'author' => ''],
    ],
];
