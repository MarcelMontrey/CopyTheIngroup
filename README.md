# Description
Psychological study examining humans' preference for copying members of the same arbitrary social group. This code was used to conduct the experiments reported in the following journal publication:

Montrey, M., & Shultz, T. R. (2022). Copy the In-group: Group Membership Trumps Perceived Reliability, Warmth, and Competence in a Social-Learning Task. *Psychological Science, 33*(1), 165-174. https://doi.org/10.1177/09567976211032224

In accordance with open data practices, all human data and code used for statistical analysis have been made publicly available via OSF:
https://doi.org/10.17605/OSF.IO/Z6D7J

These can also be found in the following GitHub repository:
https://github.com/MarcelMontrey/CopyTheIngroupData

# Abstract
Surprisingly little is known about how social groups influence social learning. Although several studies have shown that people prefer to copy ingroup members, these have failed to resolve whether group membership genuinely affects who is copied or if it merely correlates with other known factors, such as similarity and familiarity. Using the minimal group paradigm, we disentangle these effects in an online social learning game. In a sample of 540 adults, we find a robust ingroup copying bias that (1) is bolstered by a preference for observing ingroup members; (2) overrides perceived reliability, warmth, and competence; (3) grows stronger when social information is scarce; and (4) even causes cultural divergence between intermixed groups. These results suggest that people genuinely employ a copy-the-ingroup social learning strategy, which could help explain how inefficient behaviors spread through social learning and how we maintain the cultural diversity needed for cumulative cultural evolution.

# Getting Started
Upload contents to your web server. Direct participants to the `index.php` page. Data is recorded to the `users` directory. Collected data can then be compiled into both long (`data/long.tsv`) and wide formats (`data/wide.tsv`) using `tools/compile.php`.

## Details
When participants begin the experiment, they are automatically assigned to a condition, group, chain, and generation, depending on which of these are currently open (see `server/createUser.php`). A record is created in the appropriate subfolder of the `users` folder. Once the participant completes the experiment, their responses are sent to the server, where their record is updated (see `server/submitData.php`). If the participant does not complete the experiment in allotted time, their record is automatically pruned and moved to the `users/dropouts` folder (see `server/User.php`). For ease of use, code integrating with MTurk (e.g., verification that the participant has not previously started or completed the experiment) has been removed or disabled.

# License
Distributed under the MIT License. See LICENSE.txt for more information.

# Contact
Marcel Montrey - marcel.montrey@gmail.com
