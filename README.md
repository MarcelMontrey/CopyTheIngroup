# Copy the In-group
Psychological study examining humans' preference for copying members of the same arbitrary social group. This code was used to conduct the experiments reported in the following journal publication:

Montrey, M., & Shultz, T. R. (2022). Copy the In-group: Group Membership Trumps Perceived Reliability, Warmth, and Competence in a Social-Learning Task. *Psychological Science, 33*(1), 165-174. https://doi.org/10.1177/09567976211032224

# Getting Started
Upload the contents of the `study` folder to your web server. Direct participants to the `index.php` page. Data is recorded to the `users` directory.

When participants begin the experiment, they are automatically assigned to a condition, group, chain, and generation, depending on which of these are currently open (see `server/createUser.php`). A record is created in the appropriate subfolder of the `users` folder. Once the participant completes the experiment, their responses are sent to the server, where their record is updated (see `server/submitData.php`). If the participant does not complete the experiment in allotted time, their record is automatically pruned and moved to the `users/dropouts` folder (see `server/User.php`). For ease of use, code integrating with MTurk (e.g., verification that the participant has not previously started or completed the experiment) has been removed or disabled.

# License
Distributed under the MIT License. See LICENSE.txt for more information.

# Contact
Marcel Montrey - marcel.montrey@gmail.com
